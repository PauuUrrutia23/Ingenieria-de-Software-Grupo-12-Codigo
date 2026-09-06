<?php
namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Rules\PdfValido;
use App\Services\StorageAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * CertificadoController («Control») — Diagrama de Componentes: "Certificaciones".
 *
 * Único punto de entrada de las Certificaciones, tanto del lado público como del
 * Panel de Gestión, tal como lo modelan los Diagramas de Secuencia:
 *
 *   - Incremento 1: CU 24.1 (listado público), CU 25.1 (descarga del PDF) y
 *     CU 25.2 (previsualización en el navegador).
 *   - Incremento 2: CU 26.1 (registro de una nueva Certificación) y CU 34.6
 *     (módulo de Certificaciones dentro del Panel de Gestión).
 *
 * Las acciones del Panel pasan además por CheckAdminSession (C_AdminAuth en los
 * diagramas); las públicas no. Toda la persistencia va por DBRouterController.
 */
class CertificadoController extends Controller
{
    public function __construct(
        private DBRouterController $db,
        private StorageAdapter $storage,
    ) {
    }

    // ==================================================================
    // Sitio público (RF24, RF25 / CU 24.1, CU 25.1)
    // ==================================================================

    /** RF24 / CU 24.1 - Listado público de certificaciones vigentes. */
    public function listadoPublico()
    {
        $certificados = $this->db->query(Certificado::class)->where('estado', 'vigente')->orderBy('nombre')->get();

        return view('public.certificaciones', compact('certificados'));
    }

    /**
     * RF25 / CU 25.1 - Descarga del PDF del certificado.
     *
     * No se enlaza el archivo directo desde storage: pasa por el Controlador para
     * poder resolver las excepciones del caso de uso y, sobre todo, para generar
     * un nombre de archivo seguro cuando el registro no tiene uno válido
     * (CU 25.1 Excepción 4).
     */
    public function descargar(Certificado $certificado)
    {
        // CU 25.2 Excepciones 1 y 2: el certificado no tiene PDF cargado, o el archivo
        // referenciado en BD ya no está en disco.
        if (!$certificado->archivo_pdf || !$this->storage->existe($certificado->archivo_pdf)) {
            return redirect()
                ->route('public.certificaciones')
                ->with('doc_no_disponible', 'El documento solicitado no está disponible por el momento.');
        }

        // CU 25.1 Excepción 4: si el nombre almacenado no sirve, se construye uno seguro
        // a partir del Nombre de la Normativa (RF26: el formulario no captura ningún código).
        $nombreSeguro = Str::slug($certificado->nombre);
        if ($nombreSeguro === '') {
            $nombreSeguro = 'certificado-' . $certificado->id_certificado;
        }

        return $this->storage->descargar($certificado->archivo_pdf, $nombreSeguro . '.pdf');
    }

    // ==================================================================
    // Panel de Gestión (RF26 / CU 26.1, CU 34.6)
    // ==================================================================

    public function index()
    {
        $certificados = $this->db->query(Certificado::class)->orderBy('id_certificado', 'desc')->paginate(15);
        return view('admin.certificados.index', compact('certificados'));
    }

    public function create()
    {
        return view('admin.certificados.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'organismo' => 'required|string|max:120',
            'url_organismo' => 'nullable|url|max:300',
            'imagen' => 'nullable|image|max:2048',
            'archivo_pdf' => ['nullable', 'file', 'max:5120', new PdfValido()],
        ]);

        $data['id_admin'] = Auth::id();

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->storage->guardar($request->file('imagen'), 'certificados');
            $data['tipo_mime'] = $request->file('imagen')->getMimeType();
        }
        if ($request->hasFile('archivo_pdf')) {
            $data['archivo_pdf'] = $this->storage->guardar($request->file('archivo_pdf'), 'certificados_pdf');
        }

        $this->db->create(Certificado::class, $data);
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado creado.');
    }

    public function edit(Certificado $certificado)
    {
        return view('admin.certificados.edit', compact('certificado'));
    }

    public function update(Request $request, Certificado $certificado)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'estado' => 'required|in:vigente,vencido,revocado',
            'organismo' => 'required|string|max:120',
            'url_organismo' => 'nullable|url|max:300',
            'imagen' => 'nullable|image|max:2048',
            'archivo_pdf' => ['nullable', 'file', 'max:5120', new PdfValido()],
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->storage->reemplazar($certificado->imagen, $request->file('imagen'), 'certificados');
            $data['tipo_mime'] = $request->file('imagen')->getMimeType();
        }
        if ($request->hasFile('archivo_pdf')) {
            $data['archivo_pdf'] = $this->storage->reemplazar($certificado->archivo_pdf, $request->file('archivo_pdf'), 'certificados_pdf');
        }

        $this->db->update($certificado, $data);
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado actualizado.');
    }

    public function destroy(Certificado $certificado)
    {
        $this->storage->borrar($certificado->imagen);
        $this->storage->borrar($certificado->archivo_pdf);
        $this->db->delete($certificado);
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado eliminado.');
    }
}
