<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProyectoRequest;
use App\Http\Requests\UpdateProyectoRequest;
use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use App\Services\StorageAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProyectoController extends Controller
{
    public function __construct(
        private DBRouterController $db,
        private StorageAdapter $storage,
    ) {
    }

    public function galeriaPublica(Request $request)
    {
        $query = $this->db->query(Proyecto::class)->where('estado_publicacion', 'publicado')->with('imagenes');

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre_obra', 'like', '%' . $request->q . '%')
                  ->orWhere('ubicacion_geografica', 'like', '%' . $request->q . '%');
            });
        }
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        $proyectos = $query->orderBy('anio_ejecucion', 'desc')->paginate(15);

        if ($request->ajax() || $request->boolean('parcial')) {
            return view('public.partials.proyectos-grid', compact('proyectos'));
        }

        $regiones = $this->db->query(Proyecto::class)
            ->where('estado_publicacion', 'publicado')
            ->whereNotNull('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('public.proyectos', compact('proyectos', 'regiones'));
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

        $proyecto = $this->db->create(Proyecto::class, $data);

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                $this->db->create(ImagenProyecto::class, [
                    'id_proyecto' => $proyecto->id_proyecto,
                    'imagen' => $this->storage->guardar($file, 'proyectos'),
                    'nombre_archivo' => $file->getClientOriginalName(),
                    'tipo_mime' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto creado exitosamente.');
    }

    public function edit(Proyecto $proyecto)
    {
        $proyecto->load('imagenes');
        return view('admin.proyectos.edit', compact('proyecto'));
    }

    public function update(UpdateProyectoRequest $request, Proyecto $proyecto)
    {
        $this->db->update($proyecto, $request->validated());

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                $this->db->create(ImagenProyecto::class, [
                    'id_proyecto' => $proyecto->id_proyecto,
                    'imagen' => $this->storage->guardar($file, 'proyectos'),
                    'nombre_archivo' => $file->getClientOriginalName(),
                    'tipo_mime' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto actualizado.');
    }

    public function updateVisibilidad(Request $request, Proyecto $proyecto)
    {
        $data = $request->validate([
            'estado_publicacion' => 'required|in:borrador,publicado',
        ]);

        if ($proyecto->estado_publicacion === $data['estado_publicacion']) {
            return back();
        }

        if ($data['estado_publicacion'] === 'publicado' && $proyecto->imagenes()->count() === 0) {
            return back()->withErrors([
                'estado_publicacion' => 'El proyecto debe tener al menos una fotografía para poder publicarse.',
            ]);
        }

        try {
            $this->db->update($proyecto, $data);
        } catch (\Throwable $e) {
            return back()->withErrors(['estado_publicacion' => 'No se pudo actualizar la visibilidad del proyecto.']);
        }

        $etiqueta = $data['estado_publicacion'] === 'publicado' ? 'Publicado' : 'Borrador';

        return back()->with('success', "\"{$proyecto->nombre_obra}\" ahora está en estado {$etiqueta}.");
    }

    public function destroy(Proyecto $proyecto)
    {
        foreach ($proyecto->imagenes as $img) {
            $this->storage->borrar($img->imagen);
            $this->db->delete($img);
        }
        $this->db->delete($proyecto);

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    public function destroyImage(ImagenProyecto $imagen)
    {
        $this->storage->borrar($imagen->imagen);
        $this->db->delete($imagen);
        return back()->with('success', 'Imagen eliminada.');
    }
}
