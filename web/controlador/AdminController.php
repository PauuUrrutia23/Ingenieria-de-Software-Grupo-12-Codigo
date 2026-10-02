<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Consulta;
use App\Models\Contenido;
use App\Services\StorageAdapter;
use App\Support\Rnf17;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;

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
        // DS-67 / FASE 15: se recortan espacios de borde y se valida; no se
        // reescribe la cadena (se conservan nombres de marca tal cual llegan).
        $request->merge(['nombre_comercial' => trim((string) $request->input('nombre_comercial'))]);

        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:100', 'regex:/^[\p{Lu}]/u'],
            'logotipo' => array_merge(['required'], Rnf17::reglasLogo()),
        ]);

        $ruta = null;
        try {
            $data['id_admin'] = Auth::id();
            $ruta = $this->storage->guardar($request->file('logotipo'), 'colaboradores');
            $data['logotipo'] = $ruta;
            $data['tipo_mime'] = $request->file('logotipo')->getMimeType();
            $this->db->create(Colaborador::class, $data);
        } catch (\Throwable $e) {
            if ($ruta) {
                try { $this->storage->borrar($ruta); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['logotipo' => 'No se pudo crear el colaborador.']);
        }
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor creado.');
    }

    public function colaboradoresEdit(Colaborador $colaborador)
    {
        return view('admin.colaboradores.edit', compact('colaborador'));
    }

    public function colaboradoresDetalle(int $colaborador)
    {
        try {
            $registro = $this->db->query(Colaborador::class)->find($colaborador);
            if (!$registro) return response()->json(['message' => 'El colaborador ya no está disponible.'], 410);
            return response()->json([
                'id' => $registro->id_colaborador,
                'nombre' => $registro->nombre_comercial,
                'logo' => $this->storage->existe($registro->logotipo)
                    ? $this->storage->url($registro->logotipo) : null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El colaborador no está disponible temporalmente.'], 503);
        }
    }

    public function colaboradoresUpdate(Request $request, int $colaboradore)
    {
        // DS-67 / FASE 15: trim de bordes sin reescribir la marca; validación coherente.
        $request->merge(['nombre_comercial' => trim((string) $request->input('nombre_comercial'))]);

        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:100', 'regex:/^[\p{Lu}]/u'],
            'logotipo' => array_merge(['nullable'], Rnf17::reglasLogo()),
        ]);

        try {
            $colaborador = Colaborador::query()->find($colaboradore);
        } catch (\Throwable $e) {
            return back()->withErrors(['colaborador' => 'No se pudo cargar el colaborador.']);
        }
        if (!$colaborador) {
            return redirect()->route('admin.colaboradores.index')
                ->withErrors(['colaborador' => 'El colaborador ya no está disponible.']);
        }

        $logoAnterior = $colaborador->logotipo;
        $logoNuevo = null;
        try {
            if ($request->hasFile('logotipo')) {
                $logoNuevo = $this->storage->guardar($request->file('logotipo'), 'colaboradores');
                $data['logotipo'] = $logoNuevo;
                $data['tipo_mime'] = $request->file('logotipo')->getMimeType();
            }
            DB::transaction(fn () => $this->db->update($colaborador, $data));
        } catch (\Throwable $e) {
            if ($logoNuevo) {
                try { $this->storage->borrar($logoNuevo); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['colaborador' => 'No se pudo actualizar el colaborador.']);
        }
        if ($logoNuevo && $logoAnterior) {
            try { $this->storage->borrar($logoAnterior); } catch (\Throwable $e) { report($e); }
        }
        return redirect()->route('admin.colaboradores.index')->with('success', 'Proveedor actualizado.');
    }

    public function colaboradoresDestroy(int $colaboradore)
    {
        try {
            $registro = Colaborador::query()->find($colaboradore);
            if (!$registro) return redirect()->route('admin.colaboradores.index')
                ->withErrors(['colaborador' => 'El colaborador ya no está disponible.']);
            $logo = $registro->logotipo;
            DB::transaction(fn () => $this->db->delete($registro));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['colaborador' => 'No se pudo eliminar el colaborador.']);
        }
        try {
            $this->storage->borrar($logo);
        } catch (\Throwable $e) {
            report($e);
        }
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
            'archivo' => array_merge(['nullable'], Rnf17::reglasImagen()),
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

        $rutaNueva = null;
        try {
            if ($request->hasFile('archivo')) {
                $rutaNueva = $this->storage->guardar($request->file('archivo'), 'contenido');
                $data['archivo'] = $rutaNueva;
                $data['tipo_mime'] = $request->file('archivo')->getMimeType();
            }
            DB::transaction(fn () => $this->db->create(Contenido::class, $data));
        } catch (\Throwable $e) {
            if ($rutaNueva) {
                try { $this->storage->borrar($rutaNueva); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['contenido' => 'No se pudo crear el contenido. Intente nuevamente.']);
        }

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

        $rutaNueva = null;
        $rutaAnterior = $contenido->archivo;
        try {
            if ($request->hasFile('archivo')) {
                $rutaNueva = $this->storage->guardar($request->file('archivo'), 'contenido');
                $data['archivo'] = $rutaNueva;
                $data['tipo_mime'] = $request->file('archivo')->getMimeType();
            }
            DB::transaction(fn () => $this->db->update($contenido, $data));
        } catch (\Throwable $e) {
            if ($rutaNueva) {
                try { $this->storage->borrar($rutaNueva); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['contenido' => 'No se pudo actualizar el contenido. Intente nuevamente.']);
        }
        if ($rutaNueva && $rutaAnterior) {
            try { $this->storage->borrar($rutaAnterior); } catch (\Throwable $e) { report($e); }
        }

        return redirect()->route('admin.contenido.index', ['seccion' => $contenido->seccion])
            ->with('success', 'Contenido actualizado correctamente.');
    }

    public function contenidoDestroy(int $contenido)
    {
        try {
            $registro = Contenido::query()->find($contenido);
            if (!$registro) {
                return redirect()->route('admin.contenido.index')
                    ->withErrors(['contenido' => 'El contenido ya no está disponible.']);
            }
            $seccion = $registro->seccion;
            $archivo = $registro->archivo;
            DB::transaction(fn () => $this->db->delete($registro));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['contenido' => 'No se pudo eliminar el contenido.']);
        }
        try {
            $this->storage->borrar($archivo);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.contenido.index', ['seccion' => $seccion])
            ->with('success', 'Contenido eliminado correctamente.');
    }

    public function consultasIndex(Request $request)
    {
        $orden = $request->query('orden') === 'asc' ? 'asc' : 'desc';
        $q = trim((string) $request->query('q', ''));
        $q = mb_substr($q, 0, 200);

        $consulta = $this->db->query(Consulta::class)
            ->with(['visitante', 'adminResponsable'])
            ->when($q !== '', fn ($query) => $query->where('mensaje', 'like', '%' . $q . '%'))
            ->orderBy('created_at', $orden)
            ->orderBy('id_consulta', $orden);
        $consultas = $consulta->paginate(10)->withQueryString();

        if ($consultas->total() > 0 && $consultas->currentPage() > $consultas->lastPage()) {
            return redirect()->route('admin.consultas.index', [
                'q' => $q,
                'orden' => $orden,
                'page' => $consultas->lastPage(),
            ]);
        }

        return view('admin.consultas.index', compact('consultas', 'orden', 'q'));
    }

    public function consultasDetalle(int $consulta)
    {
        try {
            $registro = $this->db->query(Consulta::class)
                ->where('id_consulta', $consulta)
                ->with(['visitante', 'adminResponsable'])
                ->first();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'El detalle no está disponible temporalmente.'], 503);
        }

        if (!$registro) {
            return response()->json(['message' => 'Esta consulta ya no está disponible.'], 410);
        }

        return response()->json([
            'id' => $registro->id_consulta,
            'fecha' => $registro->created_at?->format('d/m/Y H:i'),
            'nombre' => trim(($registro->visitante?->nombre ?? '') . ' ' . ($registro->visitante?->apellido ?? '')),
            'email' => $registro->visitante?->email,
            'mensaje' => $registro->mensaje,
            'estado' => $registro->estado,
            'prioridad' => $registro->prioridad,
            'responsable' => $registro->adminResponsable?->correo,
            'notificacion_pendiente' => $registro->notificacion_admin_pendiente,
        ]);
    }

    public function consultasExportar(string $formato)
    {
        if (!in_array($formato, ['csv', 'xlsx'], true)) {
            abort(404);
        }

        try {
            $consultas = $this->db->query(Consulta::class)
                ->with(['visitante', 'adminResponsable'])
                ->orderBy('created_at')
                ->orderBy('id_consulta')
                ->get();
            if ($consultas->isEmpty()) {
                return back()->withErrors(['exportar' => 'No hay consultas para exportar.']);
            }

            $directorio = storage_path('app/exports');
            if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                throw new \RuntimeException('No se pudo preparar la exportación.');
            }
            $ruta = $directorio . DIRECTORY_SEPARATOR . 'consultas-' . bin2hex(random_bytes(8)) . '.' . $formato;
            $escritor = SimpleExcelWriter::create($ruta);
            try {
                foreach ($consultas as $consulta) {
                    $escritor->addRow([
                        'ID' => $consulta->id_consulta,
                        'Fecha' => $consulta->created_at?->format('Y-m-d H:i:s'),
                        'Nombre' => $this->textoSeguroExportacion(trim(($consulta->visitante?->nombre ?? '') . ' ' . ($consulta->visitante?->apellido ?? ''))),
                        'Correo' => $this->textoSeguroExportacion($consulta->visitante?->email),
                        'Mensaje' => $this->textoSeguroExportacion($consulta->mensaje),
                        'Estado' => $consulta->estado,
                        'Prioridad' => $consulta->prioridad,
                        'Responsable' => $this->textoSeguroExportacion($consulta->adminResponsable?->correo ?? 'Sin responsable'),
                    ]);
                }
            } finally {
                $escritor->close();
            }

            return response()->download($ruta, 'consultas-ingecon.' . $formato)
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (isset($ruta) && is_file($ruta)) {
                @unlink($ruta);
            }
            report($e);
            return back()->withErrors(['exportar' => 'La exportación no está disponible temporalmente.']);
        }
    }

    private function textoSeguroExportacion(?string $valor): string
    {
        $texto = (string) $valor;
        return preg_match('/^\s*[=+\-@]/u', $texto) ? "'" . $texto : $texto;
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

        try {
            $cambioReal = DB::transaction(function () use ($request, $consulta) {
                $actual = Consulta::query()->whereKey($consulta->id_consulta)->lockForUpdate()->first();
                if (!$actual) {
                    return null;
                }

                $prioridadNueva = $request->prioridad ?: null;
                if ($actual->estado === $request->estado && $actual->prioridad === $prioridadNueva) {
                    return false;
                }

                $this->db->update($actual, [
                    'estado' => $request->estado,
                    'prioridad' => $prioridadNueva,
                    'id_admin_responsable' => Auth::id(),
                ]);
                return true;
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['estado' => 'No se pudo actualizar el estado de la consulta.']);
        }

        if ($cambioReal === null) {
            return redirect()->route('admin.consultas.index')
                ->withErrors(['estado' => 'La consulta ya no está disponible.']);
        }
        if (!$cambioReal) {
            return back();
        }

        return back()->with('success', 'Estado de la consulta actualizado.');
    }
}
