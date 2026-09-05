<?php
namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Rules\PdfValido;

class CertificadoController extends Controller
{
    public function index()
    {
        $certificados = Certificado::orderBy('id_certificado', 'desc')->paginate(15);
        return view('admin.certificados.index', compact('certificados'));
    }

    public function create()
    {
        return view('admin.certificados.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:80|unique:certificados',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'fecha_emision' => 'required|date',
            'estado' => 'required|in:vigente,vencido,revocado',
            'organismo' => 'required|string|max:120',
            'url_organismo' => 'nullable|url|max:300',
            'imagen' => 'nullable|image|max:2048',
            'archivo_pdf' => ['nullable', 'file', 'max:5120', new PdfValido()],
        ]);

        $data['id_admin'] = Auth::id();

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('certificados', 'public');
            $data['tipo_mime'] = $request->file('imagen')->getMimeType();
        }
        if ($request->hasFile('archivo_pdf')) {
            $data['archivo_pdf'] = $request->file('archivo_pdf')->store('certificados_pdf', 'public');
        }

        Certificado::create($data);
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado creado.');
    }

    public function edit(Certificado $certificado)
    {
        return view('admin.certificados.edit', compact('certificado'));
    }

    public function update(Request $request, Certificado $certificado)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:80|unique:certificados,codigo,'.$certificado->id_certificado.',id_certificado',
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'fecha_emision' => 'required|date',
            'estado' => 'required|in:vigente,vencido,revocado',
            'organismo' => 'required|string|max:120',
            'url_organismo' => 'nullable|url|max:300',
            'imagen' => 'nullable|image|max:2048',
            'archivo_pdf' => ['nullable', 'file', 'max:5120', new PdfValido()],
        ]);

        if ($request->hasFile('imagen')) {
            if ($certificado->imagen) Storage::disk('public')->delete($certificado->imagen);
            $data['imagen'] = $request->file('imagen')->store('certificados', 'public');
            $data['tipo_mime'] = $request->file('imagen')->getMimeType();
        }
        if ($request->hasFile('archivo_pdf')) {
            if ($certificado->archivo_pdf) Storage::disk('public')->delete($certificado->archivo_pdf);
            $data['archivo_pdf'] = $request->file('archivo_pdf')->store('certificados_pdf', 'public');
        }

        $certificado->update($data);
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado actualizado.');
    }

    public function destroy(Certificado $certificado)
    {
        if ($certificado->imagen) Storage::disk('public')->delete($certificado->imagen);
        if ($certificado->archivo_pdf) Storage::disk('public')->delete($certificado->archivo_pdf);
        $certificado->delete();
        return redirect()->route('admin.certificados.index')->with('success', 'Certificado eliminado.');
    }
}