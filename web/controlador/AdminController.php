<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Consulta;
use App\Models\Contenido;
use App\Services\StorageAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct(
        private DBRouterController $db,
        private StorageAdapter $storage,
    ) {
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }

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

    public const SECCIONES = ['faq', 'opiniones', 'banner', 'fases_industriales'];

    public const NOMBRES_SECCION = [
        'faq' => 'Preguntas Frecuentes',
        'opiniones' => 'Opiniones de Clientes',
        'banner' => 'Banner de Inicio',
        'fases_industriales' => 'Fases Industriales',
    ];

    public const CAMPOS_OBLIGATORIOS = [
        'faq' => ['titulo' => 'la pregunta', 'cuerpo' => 'la respuesta'],
        'banner' => ['cuerpo' => 'el texto descriptivo'],
        'fases_industriales' => ['titulo' => 'el nombre de la fase', 'cuerpo' => 'el texto de la fase'],
        'opiniones' => ['titulo' => 'el nombre del cliente', 'cuerpo' => 'el testimonio'],
    ];

    private function contenidoReglas(string $seccion): array
    {
        $obligatorios = self::CAMPOS_OBLIGATORIOS[$seccion] ?? [];

        return [
            'titulo' => [isset($obligatorios['titulo']) ? 'required' : 'nullable', 'string', 'max:200'],
            'cuerpo' => [isset($obligatorios['cuerpo']) ? 'required' : 'nullable', 'string'],
            'archivo' => ['nullable', 'file', 'mimes:jpeg,png,webp,jpg,mp4', 'max:5120'],
        ];
    }

    private function contenidoAtributos(string $seccion): array
    {
        return self::CAMPOS_OBLIGATORIOS[$seccion] ?? [];
    }

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

    public function contenidoStore(Request $request)
    {
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

    public function contenidoEdit(Contenido $contenido)
    {
        return view('admin.contenido.edit', [
            'contenido' => $contenido,
            'nombresSeccion' => self::NOMBRES_SECCION,
        ]);
    }

    public function contenidoUpdate(Request $request, Contenido $contenido)
    {
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

    public function contenidoDestroy(Contenido $contenido)
    {
        $seccion = $contenido->seccion;

        $this->storage->borrar($contenido->archivo);
        $this->db->delete($contenido);

        return redirect()->route('admin.contenido.index', ['seccion' => $seccion])
            ->with('success', 'Contenido eliminado correctamente.');
    }

    public function consultasIndex()
    {
        $consultas = $this->db->query(Consulta::class)
            ->with(['visitante', 'adminResponsable'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.consultas.index', compact('consultas'));
    }

    public function consultasShow(Consulta $consulta)
    {
        $consulta->load(['visitante', 'adminResponsable']);
        return view('admin.consultas.show', compact('consulta'));
    }

    public function consultasUpdate(Request $request, Consulta $consulta)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,finalizada',
            'prioridad' => 'nullable|in:baja,media,alta'
        ]);

        $cambios = ['estado' => $request->estado, 'prioridad' => $request->prioridad];

        if ($request->estado != 'pendiente' && !$consulta->id_admin_responsable) {
            $cambios['id_admin_responsable'] = Auth::id();
        }

        $consulta->fill($cambios);
        if (!$consulta->isDirty()) {
            return back();
        }

        try {
            $this->db->update($consulta, $cambios);
        } catch (\Throwable $e) {
            return back()->withErrors(['estado' => 'No se pudo actualizar el estado de la consulta.']);
        }

        return back()->with('success', 'Estado de la consulta actualizado.');
    }
}
