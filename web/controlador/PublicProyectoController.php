<?php
namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\Request;

class PublicProyectoController extends Controller
{
    public function index(Request $request)
    {
        $query = Proyecto::where('estado_publicacion', 'publicado')->with('imagenes');

        if ($request->filled('q')) {
            $query->where(function($q) use ($request) {
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

        // CU 21.1: cuando los filtros combinados se aplican dinámicamente, se
        // devuelve solo el fragmento de resultados y la vista lo inyecta sin recargar.
        if ($request->ajax() || $request->boolean('parcial')) {
            return view('public.partials.proyectos-grid', compact('proyectos'));
        }

        $regiones = Proyecto::where('estado_publicacion', 'publicado')
            ->whereNotNull('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('public.proyectos', compact('proyectos', 'regiones'));
    }
}