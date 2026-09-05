<?php
namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\ImagenProyecto;
use App\Http\Requests\StoreProyectoRequest;
use App\Http\Requests\UpdateProyectoRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProyectoController extends Controller
{
    public function index()
    {
        $proyectos = Proyecto::with('imagenes')->orderBy('id_proyecto', 'desc')->paginate(15);
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

        $proyecto = Proyecto::create($data);

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                $path = $file->store('proyectos', 'public');
                ImagenProyecto::create([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'imagen' => $path,
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
        $proyecto->update($request->validated());

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                $path = $file->store('proyectos', 'public');
                ImagenProyecto::create([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'imagen' => $path,
                    'nombre_archivo' => $file->getClientOriginalName(),
                    'tipo_mime' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto actualizado.');
    }

    /**
     * RF50 / CU 50.1 - Cambia la visibilidad desde el menú desplegable de la propia
     * tarjeta, sin pasar por el formulario de edición completo.
     */
    public function updateVisibilidad(\Illuminate\Http\Request $request, Proyecto $proyecto)
    {
        $data = $request->validate([
            'estado_publicacion' => 'required|in:borrador,publicado',
        ]);

        // Excepción 1: se selecciona el mismo estado ya vigente → no genera transacción.
        if ($proyecto->estado_publicacion === $data['estado_publicacion']) {
            return back();
        }

        // CU 48.2 (Publicando Proyectos): un proyecto no puede publicarse sin al
        // menos una fotografía cargada. Documentado en las pruebas unitarias
        // originales ("Intentar publicar un proyecto sin imágenes").
        if ($data['estado_publicacion'] === 'publicado' && $proyecto->imagenes()->count() === 0) {
            return back()->withErrors([
                'estado_publicacion' => 'El proyecto debe tener al menos una fotografía para poder publicarse.',
            ]);
        }

        try {
            $proyecto->update($data);
        } catch (\Throwable $e) {
            // Excepción 2: la BD no permite actualizar → conserva el estado anterior.
            return back()->withErrors(['estado_publicacion' => 'No se pudo actualizar la visibilidad del proyecto.']);
        }

        $etiqueta = $data['estado_publicacion'] === 'publicado' ? 'Publicado' : 'Borrador';

        return back()->with('success', "\"{$proyecto->nombre_obra}\" ahora está en estado {$etiqueta}.");
    }

    public function destroy(Proyecto $proyecto)
    {
        foreach ($proyecto->imagenes as $img) {
            Storage::disk('public')->delete($img->imagen);
            $img->delete();
        }
        $proyecto->delete();

        return redirect()->route('admin.proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    public function destroyImage(ImagenProyecto $imagen)
    {
        Storage::disk('public')->delete($imagen->imagen);
        $imagen->delete();
        return back()->with('success', 'Imagen eliminada.');
    }
}