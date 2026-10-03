<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Rules\PdfValido;
use App\Services\StorageAdapter;
use App\Support\Rnf17;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        try {
            $certificados = $this->db->query(Certificado::class)->where('estado', 'vigente')->orderBy('nombre')->get();
        } catch (\Throwable $e) {
            $certificados = collect();
        }

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

    public function preview(Certificado $certificado)
    {
        if ($certificado->estado !== 'vigente') {
            return redirect()
                ->route('public.certificaciones')
                ->with('doc_no_disponible', 'El documento solicitado no está disponible por el momento.');
        }

        if (!$certificado->archivo_pdf || !$this->storage->existe($certificado->archivo_pdf)) {
            return redirect()
                ->route('public.certificaciones')
                ->with('doc_no_disponible', 'El documento solicitado no está disponible por el momento.');
        }

        $nombreSeguro = Str::slug($certificado->nombre);
        if ($nombreSeguro === '') {
            $nombreSeguro = 'certificado-' . $certificado->id_certificado;
        }

        return $this->storage->responderInline($certificado->archivo_pdf, $nombreSeguro . '.pdf');
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
            'imagen' => array_merge(['nullable'], Rnf17::reglasImagen()),
            'archivo_pdf' => ['nullable', 'file', 'max:5120', new PdfValido()],
        ]);

        $data['id_admin'] = Auth::id();

        $escritos = [];
        try {
            if ($request->hasFile('imagen')) {
                $data['imagen'] = $this->storage->guardar($request->file('imagen'), 'certificados');
                $escritos[] = $data['imagen'];
                $data['tipo_mime'] = $request->file('imagen')->getMimeType();
            }
            if ($request->hasFile('archivo_pdf')) {
                $data['archivo_pdf'] = $this->storage->guardar($request->file('archivo_pdf'), 'certificados_pdf');
                $escritos[] = $data['archivo_pdf'];
            }
            DB::transaction(fn () => $this->db->create(Certificado::class, $data));
        } catch (\Throwable $e) {
            foreach ($escritos as $ruta) {
                try { $this->storage->borrar($ruta); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['archivo_pdf' => 'No se pudo crear el certificado.']);
        }
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
            'imagen' => array_merge(['nullable'], Rnf17::reglasImagen()),
            'archivo_pdf' => ['nullable', 'file', 'max:5120', new PdfValido()],
        ]);

        $anteriores = [];
        $nuevos = [];
        try {
            if ($request->hasFile('imagen')) {
                $data['imagen'] = $this->storage->guardar($request->file('imagen'), 'certificados');
                $nuevos[] = $data['imagen'];
                $anteriores[] = $certificado->imagen;
                $data['tipo_mime'] = $request->file('imagen')->getMimeType();
            }
            if ($request->hasFile('archivo_pdf')) {
                $data['archivo_pdf'] = $this->storage->guardar($request->file('archivo_pdf'), 'certificados_pdf');
                $nuevos[] = $data['archivo_pdf'];
                $anteriores[] = $certificado->archivo_pdf;
            }
            DB::transaction(fn () => $this->db->update($certificado, $data));
        } catch (\Throwable $e) {
            foreach ($nuevos as $ruta) {
                try { $this->storage->borrar($ruta); } catch (\Throwable $cleanupError) { report($cleanupError); }
            }
            report($e);
            return back()->withInput()->withErrors(['archivo_pdf' => 'No se pudo actualizar el certificado.']);
        }
        foreach ($anteriores as $ruta) {
            try { $this->storage->borrar($ruta); } catch (\Throwable $e) { report($e); }
        }
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado actualizado.');
    }

    public function destroy(int $certificado)
    {
        try {
            $registro = Certificado::query()->find($certificado);
            if (!$registro) return redirect()->route('admin.certificados.index')
                ->withErrors(['certificado' => 'El certificado ya no está disponible.']);
            $rutas = [$registro->imagen, $registro->archivo_pdf];
            DB::transaction(fn () => $this->db->delete($registro));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['certificado' => 'No se pudo eliminar el certificado.']);
        }
        foreach ($rutas as $ruta) {
            try { $this->storage->borrar($ruta); } catch (\Throwable $e) { report($e); }
        }
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado eliminado.');
    }
}
