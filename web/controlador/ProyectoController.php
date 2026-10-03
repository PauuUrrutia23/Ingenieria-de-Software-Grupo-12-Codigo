<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProyectoRequest;
use App\Http\Requests\UpdateProyectoRequest;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use App\Services\StorageAdapter;
use App\Support\CategoriasProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ProyectoController extends Controller
{
    public function __construct(
        private DBRouterController $db,
        private StorageAdapter $storage,
    ) {
    }

    public function galeriaPublica(Request $request)
    {
        $filtros = $this->filtrosPublicos($request);
        $galeriaNoDisponible = false;
        try {
            $query = $this->consultaPublica($filtros)->with('imagenes');
            $proyectos = $query->orderBy('anio_ejecucion', 'desc')->paginate(15)->withQueryString();
        } catch (\Throwable $e) {
            $galeriaNoDisponible = true;
            $proyectos = new LengthAwarePaginator([], 0, 15, LengthAwarePaginator::resolveCurrentPage(), [
                'path' => $request->url(), 'query' => $request->query(),
            ]);
        }

        if ($request->ajax() || $request->boolean('parcial')) {
            return view('public.partials.proyectos-grid', compact('proyectos', 'galeriaNoDisponible'));
        }

        try {
            $regiones = $this->db->query(Proyecto::class)
                ->where('estado_publicacion', 'publicado')->whereNotNull('region')
                ->distinct()->orderBy('region')->pluck('region');
        } catch (\Throwable $e) {
            $regiones = collect();
        }
        try {
            $marcadores = $this->datosMarcadores($filtros);
        } catch (\Throwable $e) {
            $marcadores = collect();
        }

        return view('public.proyectos', compact('proyectos', 'regiones', 'marcadores', 'galeriaNoDisponible'));
    }

    private function filtrosPublicos(Request $request): array
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'categoria' => ['nullable', Rule::in(CategoriasProyecto::valores())],
            'region' => ['nullable', 'string', 'max:80'],
        ]);

        return [
            'q' => trim($datos['q'] ?? ''),
            'categoria' => $datos['categoria'] ?? '',
            'region' => trim($datos['region'] ?? ''),
        ];
    }

    private function consultaPublica(array $filtros)
    {
        $query = $this->db->query(Proyecto::class)->where('estado_publicacion', 'publicado');

        if ($filtros['q'] !== '') {
            $texto = $filtros['q'];
            $query->where(function ($subconsulta) use ($texto) {
                $subconsulta->where('nombre_obra', 'like', '%' . $texto . '%')
                    ->orWhere('comuna', 'like', '%' . $texto . '%')
                    ->orWhere('region', 'like', '%' . $texto . '%');
            });
        }
        if ($filtros['categoria'] !== '') {
            $query->where('categoria', $filtros['categoria']);
        }
        if ($filtros['region'] !== '') {
            $query->where('region', $filtros['region']);
        }

        return $query;
    }

    public function detallePublico(int $proyecto)
    {
        try {
            $registro = $this->db->query(Proyecto::class)
                ->where('id_proyecto', $proyecto)
                ->where('estado_publicacion', 'publicado')
                ->with('imagenes')
                ->first();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El detalle no está disponible temporalmente.'], 503);
        }

        if (!$registro) {
            return response()->json(['message' => 'Este proyecto ya no está disponible.'], 410);
        }

        try {
            $imagenes = $registro->imagenes
                ->filter(fn ($imagen) => $this->storage->existe($imagen->imagen))
                ->map(fn ($imagen) => $this->storage->url($imagen->imagen))
                ->values();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El detalle no está disponible temporalmente.'], 503);
        }

        return response()->json([
            'id' => $registro->id_proyecto,
            'nombre' => $registro->nombre_obra,
            'descripcion' => $registro->descripcion_tecnica,
            'ubicacion' => trim($registro->comuna . ', ' . $registro->region, ', '),
            'categoria' => CategoriasProyecto::etiqueta($registro->categoria),
            'anio' => $registro->anio_ejecucion,
            'imagenes' => $imagenes,
        ]);
    }

    public function marcadoresPublicos(Request $request)
    {
        try {
            return response()->json($this->datosMarcadores($this->filtrosPublicos($request)));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El mapa no está disponible temporalmente.'], 503);
        }
    }

    private function datosMarcadores(array $filtros)
    {
        return $this->consultaPublica($filtros)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->orderBy('id_proyecto')
            ->get()
            ->map(function (Proyecto $proyecto) {
                return [
                    'id' => $proyecto->id_proyecto,
                    'nombre' => $proyecto->nombre_obra,
                    'anio' => $proyecto->anio_ejecucion,
                    'latitud' => (float) $proyecto->latitud,
                    'longitud' => (float) $proyecto->longitud,
                ];
            })->values();
    }

    public function index()
    {
        $proyectos = $this->db->query(Proyecto::class)->with('imagenes')->orderBy('id_proyecto', 'desc')->paginate(15);
        return view('admin.proyectos.index', compact('proyectos'));
    }

    public function create()
    {
        return view('admin.proyectos.create');
    }

    public function store(StoreProyectoRequest $request)
    {
        $data = $request->validated();
        $data['id_admin'] = Auth::id();
        // Todo proyecto nuevo nace como borrador.
        $data['estado_publicacion'] = 'borrador';

        // Si falla una imagen se revierte todo y se borran los archivos ya guardados.
        $archivosEscritos = [];

        try {
            DB::transaction(function () use ($request, $data, &$archivosEscritos) {
                $proyecto = $this->db->create(Proyecto::class, $data);

                if ($request->hasFile('imagenes')) {
                    foreach ($request->file('imagenes') as $file) {
                        $ruta = $this->storage->guardar($file, 'proyectos');
                        $archivosEscritos[] = $ruta;

                        $this->db->create(ImagenProyecto::class, [
                            'id_proyecto' => $proyecto->id_proyecto,
                            'imagen' => $ruta,
                            'nombre_archivo' => $file->getClientOriginalName(),
                            'tipo_mime' => $file->getMimeType(),
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            foreach ($archivosEscritos as $ruta) {
                $this->storage->borrar($ruta);
            }

            report($e);

            return back()->withInput()->withErrors([
                'imagenes' => 'No se pudo crear el proyecto. Intente nuevamente.',
            ]);
        }

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto creado exitosamente.');
    }

    public function edit(Proyecto $proyecto)
    {
        $proyecto->load('imagenes');
        return view('admin.proyectos.edit', compact('proyecto'));
    }

    public function detalleEdicion(int $proyecto)
    {
        try {
            $registro = Proyecto::query()->withCount('imagenes')->find($proyecto);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'No se pudo cargar el proyecto. Intente nuevamente.'], 503);
        }

        if (!$registro) {
            return response()->json(['message' => 'El proyecto ya no está disponible. Actualice el listado.'], 404);
        }

        return response()->json($registro->only([
            'id_proyecto', 'nombre_obra', 'categoria', 'anio_ejecucion', 'region',
            'comuna', 'latitud', 'longitud', 'estado_publicacion',
            'descripcion_tecnica', 'imagenes_count',
        ]));
    }

    public function update(UpdateProyectoRequest $request, Proyecto $proyecto)
    {
        // Máximo 15 imágenes contando las que ya tiene.
        $nuevas = $request->hasFile('imagenes') ? count($request->file('imagenes')) : 0;
        $totalImagenes = $proyecto->imagenes()->count() + $nuevas;
        if ($totalImagenes > 15) {
            return back()->withInput()->withErrors([
                'imagenes' => 'El proyecto no puede superar 15 fotografías en total.',
            ]);
        }

        if ($request->input('estado_publicacion') === 'publicado' && $totalImagenes === 0) {
            return back()->withInput()->withErrors([
                'estado_publicacion' => 'El proyecto debe tener al menos una fotografía para poder publicarse.',
            ]);
        }

        $datos = collect($request->validated())->except('imagenes')->all();
        $archivosEscritos = [];
        try {
            DB::transaction(function () use ($proyecto, $datos, $request, &$archivosEscritos) {
                $this->db->update($proyecto, $datos);
                foreach ($request->file('imagenes', []) as $file) {
                    $ruta = $this->storage->guardar($file, 'proyectos');
                    $archivosEscritos[] = $ruta;
                    $this->db->create(ImagenProyecto::class, [
                        'id_proyecto' => $proyecto->id_proyecto,
                        'imagen' => $ruta,
                        'nombre_archivo' => $file->getClientOriginalName(),
                        'tipo_mime' => $file->getMimeType(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($archivosEscritos as $ruta) {
                try { $this->storage->borrar($ruta); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['proyecto' => 'No se pudo actualizar el proyecto. Intente nuevamente.']);
        }

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto actualizado.');
    }

    public function updateVisibilidad(Request $request, int $proyecto)
    {
        $data = $request->validate([
            'estado_publicacion' => 'required|in:borrador,publicado',
        ]);

        try {
            $registro = Proyecto::query()->find($proyecto);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['estado_publicacion' => 'No se pudo consultar el proyecto. Intente nuevamente.']);
        }

        if (!$registro) {
            return redirect()->route('admin.proyectos.index')
                ->withErrors(['estado_publicacion' => 'El proyecto ya no está disponible. Actualice el listado.']);
        }

        if ($registro->estado_publicacion === $data['estado_publicacion']) {
            return back();
        }

        if ($data['estado_publicacion'] === 'publicado' && $registro->imagenes()->count() === 0) {
            return back()->withErrors([
                'estado_publicacion' => 'El proyecto debe tener al menos una fotografía para poder publicarse.',
            ]);
        }

        try {
            $this->db->update($registro, $data);
        } catch (\Throwable $e) {
            return back()->withErrors(['estado_publicacion' => 'No se pudo actualizar la visibilidad del proyecto.']);
        }

        $etiqueta = $data['estado_publicacion'] === 'publicado' ? 'Publicado' : 'Borrador';

        return back()->with('success', "\"{$registro->nombre_obra}\" ahora está en estado {$etiqueta}.");
    }

    public function destroy(int $proyecto)
    {
        try {
            $rutas = DB::transaction(function () use ($proyecto) {
                $registro = Proyecto::query()->whereKey($proyecto)->lockForUpdate()->first();
                if (!$registro) return null;
                $imagenes = $registro->imagenes;
                $rutas = $imagenes->pluck('imagen')->all();
                foreach ($imagenes as $imagen) $this->db->delete($imagen);
                $this->db->delete($registro);
                return $rutas;
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['proyecto' => 'No se pudo eliminar el proyecto.']);
        }

        if ($rutas === null) {
            return redirect()->route('admin.proyectos.index')
                ->withErrors(['proyecto' => 'El proyecto ya no está disponible.']);
        }
        foreach ($rutas as $ruta) {
            try { $this->storage->borrar($ruta); } catch (\Throwable $e) { report($e); }
        }

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    public function destroyImage(ImagenProyecto $imagen)
    {
        $this->storage->borrar($imagen->imagen);
        $this->db->delete($imagen);
        return back()->with('success', 'Imagen eliminada.');
    }
}
