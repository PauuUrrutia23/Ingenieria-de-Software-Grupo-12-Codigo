<?php
namespace App\Http\Controllers;

use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsultaController extends Controller
{
    public function index(Request $request)
    {
        $query = Consulta::with(['visitante', 'adminResponsable'])->orderBy('created_at', 'desc');
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        $consultas = $query->paginate(10);
        return view('admin.consultas.index', compact('consultas'));
    }

    public function show(Consulta $consulta)
    {
        $consulta->load(['visitante', 'adminResponsable']);
        return view('admin.consultas.show', compact('consulta'));
    }

    public function update(Request $request, Consulta $consulta)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,finalizada',
            'prioridad' => 'nullable|in:baja,media,alta'
        ]);

        $consulta->estado = $request->estado;
        $consulta->prioridad = $request->prioridad;
        
        // Si asume la responsabilidad (pasa a en_proceso y no tiene responsable)
        if ($request->estado != 'pendiente' && !$consulta->id_admin_responsable) {
            $consulta->id_admin_responsable = Auth::id();
        }

        // CU 41.1 Excepcion 1: si se elige el mismo estado vigente, no se genera transaccion.
        if (!$consulta->isDirty()) {
            return back();
        }

        try {
            $consulta->save();
        } catch (\Throwable $e) {
            // CU 41.1 Excepcion 2: conserva el estado anterior visible.
            return back()->withErrors(['estado' => 'No se pudo actualizar el estado de la consulta.']);
        }

        // Se vuelve al origen: el selector vive en el modal de detalle del listado (RF41).
        return back()->with('success', 'Estado de la consulta actualizado.');
    }
}