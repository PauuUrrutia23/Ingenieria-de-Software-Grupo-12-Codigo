<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Rules\PdfValido;
use App\Services\StorageAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CertificadoController extends Controller
{
    public function __construct(
        private DBRouterController $db,
        private StorageAdapter $storage,
    ) {
    }

    public function listadoPublico()
    {
        $certificados = $this->db->query(Certificado::class)->where('estado', 'vigente')->orderBy('nombre')->get();

        return view('public.certificaciones', compact('certificados'));
    }

    public function descargar(Certificado $certificado)
    {
        if (!$certificado->archivo_pdf || !$this->storage->existe($certificado->archivo_pdf)) {
            return redirect()
                ->route('public.certificaciones')
                ->with('doc_no_disponible', 'El documento solicitado no está disponible por el momento.');
        }

        $nombreSeguro = Str::slug($certificado->nombre);
        if ($nombreSeguro === '') {
            $nombreSeguro = 'certificado-' . $certificado->id_certificado;
        }

        return $this->storage->descargar($certificado->archivo_pdf, $nombreSeguro . '.pdf');
    }

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
