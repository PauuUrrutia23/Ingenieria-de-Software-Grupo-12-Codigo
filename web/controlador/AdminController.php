<?php
namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Consulta;
use App\Models\Contenido;
use App\Services\StorageAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AdminController («Control») — Diagrama de Componentes: "Dashboard y CRUD".
 *
 * Agrupa los módulos del Panel de Gestión que no tienen su propio nodo en el
 * diagrama de componentes: el Dashboard de bienvenida, Colaboradores (RF45-47) y el Panel de contenido multimedia (RF43-44). Cada
 * módulo mantiene sus propios nombres de método (prefijo `colaboradores*`,
 * `contenido*`, `consultas*`) para no chocar entre sí dentro de una sola clase.
 */
class AdminController extends Controller
{
    public function __construct(
        private DBRouterController $db,
        private StorageAdapter $storage,
    ) {
    }

    // ==================================================================
    // Dashboard
    // ==================================================================

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    // ==================================================================
    // Colaboradores (RF45-47 / CU 45.1, 45.2, 46.1, 47.1, CU 34.4)
    // ==================================================================

    public function colaboradoresIndex()
    {
        $colaboradores = $this->db->query(Colaborador::class)->orderBy('id_colaborador', 'desc')->paginate(15);
        return view('admin.colaboradores.index', compact('colaboradores'));
    }

    public function colaboradoresCreate()
    {
        return view('admin.colaboradores.create');
    }

    public function colaboradoresStore(Request $request)
    {
        $data = $request->validate([
            'nombre_comercial' => 'required|string|max:120',
            'logotipo' => 'required|image|max:500',
        ]);

        $data['id_admin'] = Auth::id();
        $data['logotipo'] = $this->storage->guardar($request->file('logotipo'), 'colaboradores');
        $data['tipo_mime'] = $request->file('logotipo')->getMimeType();

        $this->db->create(Colaborador::class, $data);
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor creado.');
    }

    public function colaboradoresEdit(Colaborador $colaborador)
    {
        return view('admin.colaboradores.edit', compact('colaborador'));
    }

    public function colaboradoresUpdate(Request $request, Colaborador $colaborador)
    {
        $data = $request->validate([
            'nombre_comercial' => 'required|string|max:120',
            'logotipo' => 'nullable|image|max:500',
        ]);

        if ($request->hasFile('logotipo')) {
            $data['logotipo'] = $this->storage->reemplazar($colaborador->logotipo, $request->file('logotipo'), 'colaboradores');
            $data['tipo_mime'] = $request->file('logotipo')->getMimeType();
        }

        $this->db->update($colaborador, $data);
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor actualizado.');
    }

    public function colaboradoresDestroy(Colaborador $colaborador)
    {
        $this->storage->borrar($colaborador->logotipo);
        $this->db->delete($colaborador);
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor eliminado.');
    }

    // ==================================================================
    // Panel de gestión — Contenido multimedia (RF43-44 / CU 34.5)
    // ==================================================================

    public const SECCIONES = ['faq', 'opiniones', 'banner', 'fases_industriales'];

    public const NOMBRES_SECCION = [
        'faq' => 'Preguntas Frecuentes',
        'opiniones' => 'Opiniones de Clientes',
        'banner' => 'Banner de Inicio',
        'fases_industriales' => 'Fases Industriales',
    ];

    /**
     * Campos obligatorios por sección. Cada sub-caso de uso de RF43 define los suyos
     * y exige rechazar el guardado si vienen vacíos:
     *   CU 43.2 FAQ            → Pregunta (titulo) + Respuesta (cuerpo)
     *   CU 43.3 Banner         → Texto Descriptivo (cuerpo)
     *   CU 43.4 Fases          → Nombre de la Fase (titulo) + Texto (cuerpo)
     *   CU 43.5 Opiniones      → Nombre del Cliente (titulo) + Testimonio (cuerpo)
     */
    public const CAMPOS_OBLIGATORIOS = [
        'faq' => ['titulo' => 'la pregunta', 'cuerpo' => 'la respuesta'],
        'banner' => ['cuerpo' => 'el texto descriptivo'],
        'fases_industriales' => ['titulo' => 'el nombre de la fase', 'cuerpo' => 'el texto de la fase'],
        'opiniones' => ['titulo' => 'el nombre del cliente', 'cuerpo' => 'el testimonio'],
    ];

    /** Reglas de validación para una sección concreta. */
    private function contenidoReglas(string $seccion): array
    {
        $obligatorios = self::CAMPOS_OBLIGATORIOS[$seccion] ?? [];

        return [
            'titulo' => [isset($obligatorios['titulo']) ? 'required' : 'nullable', 'string', 'max:200'],
            'cuerpo' => [isset($obligatorios['cuerpo']) ? 'required' : 'nullable', 'string'],
            'archivo' => ['nullable', 'file', 'mimes:jpeg,png,webp,jpg,mp4', 'max:5120'],
        ];
    }

    /** Nombres legibles por sección, para que el mensaje de error tenga sentido (RNF10). */
    private function contenidoAtributos(string $seccion): array
    {
        return self::CAMPOS_OBLIGATORIOS[$seccion] ?? [];
    }

    /** CU 34.5 - Accediendo al Panel de gestión (contenido multimedia). */
    public function contenidoIndex(Request $request)
    {
        $seccionActual = $request->get('seccion', 'faq');
        if (!in_array($seccionActual, self::SECCIONES)) {
            $seccionActual = 'faq';
        }

        $contenidos = $this->db->query(Contenido::class)->where('seccion', $seccionActual)->orderBy('id_contenido')->get();

        return view('admin.contenido.index', [
            'contenidos' => $contenidos,
            'seccionActual' => $seccionActual,
            'secciones' => self::SECCIONES,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    /** CU 43.2 / 43.3 / 43.4 / 43.5 - Añadiendo contenido multimedia (formulario). */
    public function contenidoCreate(Request $request)
    {
        $seccion = $request->get('seccion', 'faq');
        if (!in_array($seccion, self::SECCIONES)) {
            $seccion = 'faq';
        }

        return view('admin.contenido.create', [
            'seccion' => $seccion,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    /** CU 43.2 / 43.3 / 43.4 / 43.5 - Guardando el nuevo contenido. */
    public function contenidoStore(Request $request)
    {
        // La sección se valida primero: de ella dependen los campos obligatorios.
        $seccion = $request->validate([
            'seccion' => ['required', 'in:' . implode(',', self::SECCIONES)],
        ])['seccion'];

        $data = $request->validate(
            $this->contenidoReglas($seccion),
            [],
            $this->contenidoAtributos($seccion)
        );
        $data['seccion'] = $seccion;
        $data['id_admin'] = Auth::id();
        $data['activo'] = true;
        $data['orden'] = $data['orden'] ?? 0;

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $this->storage->guardar($request->file('archivo'), 'contenido');
            $data['tipo_mime'] = $request->file('archivo')->getMimeType();
        }

        $this->db->create(Contenido::class, $data);

        return redirect()->route('admin.contenido.index', ['seccion' => $data['seccion']])
            ->with('success', 'Contenido agregado correctamente.');
    }

    /** CU 43.6 / 43.7 / 43.8 / 43.9 - Actualizando contenido multimedia (formulario). */
    public function contenidoEdit(Contenido $contenido)
    {
        return view('admin.contenido.edit', [
            'contenido' => $contenido,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    /** CU 43.6 / 43.7 / 43.8 / 43.9 - Guardando la actualización. */
    public function contenidoUpdate(Request $request, Contenido $contenido)
    {
        // CU 43.6-43.9: al actualizar rigen los mismos campos obligatorios de la sección.
        $data = $request->validate(
            $this->contenidoReglas($contenido->seccion),
            [],
            $this->contenidoAtributos($contenido->seccion)
        );

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $this->storage->reemplazar($contenido->archivo, $request->file('archivo'), 'contenido');
            $data['tipo_mime'] = $request->file('archivo')->getMimeType();
        }

        $this->db->update($contenido, $data);

        return redirect()->route('admin.contenido.index', ['seccion' => $contenido->seccion])
            ->with('success', 'Contenido actualizado correctamente.');
    }

    /** CU 44.1 - 44.5 - Eliminando contenido multimedia. */
    public function contenidoDestroy(Contenido $contenido)
    {
        $seccion = $contenido->seccion;

        $this->storage->borrar($contenido->archivo);
        $this->db->delete($contenido);

        return redirect()->route('admin.contenido.index', ['seccion' => $seccion])
            ->with('success', 'Contenido eliminado correctamente.');
    }

    // ==================================================================
    // Consultas Comerciales (RF36, RF39, RF41 / CU 36.1, 36.2, 39.1, 41.1)
    // ==================================================================

    /**
     * RF36 / CU 36.1 - Historial de Consultas en bloques de 10.
     *
     * El orden es fijo, de la más reciente a la más antigua: el control de
     * ordenamiento es RF37 (UR 6.4) y la búsqueda por texto es RF38 (UR 6.5),
     * ambos de Prioridad 3, o sea Incremento 3.
     */
    public function consultasIndex()
    {
        $consultas = $this->db->query(Consulta::class)
            ->with(['visitante', 'adminResponsable'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.consultas.index', compact('consultas'));
    }

    /** RF39 / CU 39.1 - Detalle de una Consulta en la Ventana Modal. */
    public function consultasShow(Consulta $consulta)
    {
        $consulta->load(['visitante', 'adminResponsable']);
        return view('admin.consultas.show', compact('consulta'));
    }

    /** RF41 / CU 41.1 - Actualización del Estado de la Consulta. */
    public function consultasUpdate(Request $request, Consulta $consulta)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,finalizada',
            'prioridad' => 'nullable|in:baja,media,alta'
        ]);

        $cambios = ['estado' => $request->estado, 'prioridad' => $request->prioridad];

        // Si asume la responsabilidad (pasa a en_proceso y no tiene responsable)
        if ($request->estado != 'pendiente' && !$consulta->id_admin_responsable) {
            $cambios['id_admin_responsable'] = Auth::id();
        }

        // CU 41.1 Excepcion 1: si se elige el mismo estado vigente, no se genera transaccion.
        $consulta->fill($cambios);
        if (!$consulta->isDirty()) {
            return back();
        }

        try {
            $this->db->update($consulta, $cambios);
        } catch (\Throwable $e) {
            // CU 41.1 Excepcion 2: conserva el estado anterior visible.
            return back()->withErrors(['estado' => 'No se pudo actualizar el estado de la consulta.']);
        }

        // Se vuelve al origen: el selector vive en el modal de detalle del listado (RF41).
        return back()->with('success', 'Estado de la consulta actualizado.');
    }
}
