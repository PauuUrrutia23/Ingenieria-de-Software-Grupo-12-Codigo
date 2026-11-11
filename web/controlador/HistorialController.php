<?php

namespace App\Http\Controllers;

use App\Models\HistorialCambio;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

// RF15: Módulo de Historial de Cambios, solo para el Administrador jefe.
class HistorialController extends Controller
{
    public function __construct(private DBRouterController $db)
    {
    }

    public function index(Request $request)
    {
        $tipo = array_key_exists($request->query('tipo'), HistorialCambio::TIPOS) ? $request->query('tipo') : 'todos';

        $responsable = (string) $request->query('responsable', '');
        $modulo = array_key_exists($request->query('modulo'), HistorialCambio::MODULOS) ? $request->query('modulo') : '';
        $q = trim((string) $request->query('q', ''));
        $desde = $this->fecha($request->query('desde'));
        $hasta = $this->fecha($request->query('hasta'));
        // CU 15.2, Excepción 1: el rango no se aplica si "Desde" es posterior a "Hasta".
        if ($desde && $hasta && $desde > $hasta) {
            return redirect()->route('admin.historial.index', $request->except(['desde', 'hasta']))
                ->withErrors(['desde' => 'La fecha "Desde" no puede ser posterior a la fecha "Hasta".'])
                ->withInput();
        }

        $consulta = $this->db->query(HistorialCambio::class);
        if ($tipo !== 'todos') {
            $consulta->whereIn('accion', HistorialCambio::TIPOS[$tipo]);
        }
        if ($responsable !== '') {
            $consulta->where('correo_responsable', $responsable);
        }
        if ($modulo !== '') {
            $consulta->where('modulo', $modulo);
        }
        if ($desde) {
            $consulta->whereDate('fecha_hora', '>=', $desde);
        }
        if ($hasta) {
            $consulta->whereDate('fecha_hora', '<=', $hasta);
        }
        if ($q !== '') {
            // El texto se busca en el correo y en las etiquetas visibles del módulo y la acción.
            $coincide = fn (array $etiquetas) => array_keys(array_filter($etiquetas, fn ($e) => mb_stripos($e, $q) !== false));
            $consulta->where(fn ($w) => $w->where('correo_responsable', 'like', '%' . $q . '%')
                ->orWhereIn('modulo', $coincide(HistorialCambio::MODULOS))
                ->orWhereIn('accion', $coincide(HistorialCambio::ACCIONES)));
        }
        $errorCarga = false;
        try {
            $registros = $consulta->orderByDesc('fecha_hora')->orderByDesc('id_historial')->paginate(15)->withQueryString();
            $responsables = $this->db->query(HistorialCambio::class)->distinct()->orderBy('correo_responsable')->pluck('correo_responsable');
        } catch (\Throwable $e) {
            // CU 15.1, Excepción 2.
            report($e);
            $errorCarga = true;
            $registros = new LengthAwarePaginator([], 0, 15);
            $responsables = collect();
        }

        return view('admin.historial.index', compact('registros', 'tipo', 'responsable', 'modulo', 'q', 'desde', 'hasta', 'responsables', 'errorCarga'));
    }

    private function fecha($valor): ?string
    {
        return is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : null;
    }

    // CU 15.3: detalle con los valores anteriores y nuevos, cuando corresponda.
    public function detalle(int $registro)
    {
        try {
            $r = $this->db->query(HistorialCambio::class)->find($registro);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El detalle no puede mostrarse temporalmente.'], 503);
        }
        if (!$r) {
            return response()->json(['message' => 'El registro no existe.'], 404);
        }

        $anteriores = json_decode((string) $r->valores_anteriores, true) ?: [];
        $nuevos = json_decode((string) $r->valores_nuevos, true) ?: [];
        $cambios = [];
        foreach (array_unique(array_merge(array_keys($anteriores), array_keys($nuevos))) as $campo) {
            $cambios[] = [
                'campo' => $r->etiquetaCampo($campo),
                'anterior' => $r->valorLegible($campo, $anteriores[$campo] ?? null),
                'nuevo' => $r->valorLegible($campo, $nuevos[$campo] ?? null),
            ];
        }

        return response()->json([
            'fecha' => $r->fecha_hora->format('d/m/Y H:i:s'),
            'responsable' => $r->correo_responsable,
            'cuenta_eliminada' => $r->id_admin === null,
            'accion' => $r->accion_etiqueta,
            'modulo' => $r->modulo_etiqueta,
            'id_registro' => $r->id_registro_afectado,
            'cambios' => $cambios,
        ]);
    }
}
