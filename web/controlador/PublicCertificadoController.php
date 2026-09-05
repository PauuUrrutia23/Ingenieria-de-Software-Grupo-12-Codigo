<?php
namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicCertificadoController extends Controller
{
    /** RF24 / CU 24.1 - listado público de certificaciones vigentes */
    public function index()
    {
        $certificados = Certificado::where('estado', 'vigente')->orderBy('nombre')->get();

        return view('public.certificaciones', compact('certificados'));
    }

    /**
     * RF25 / CU 25.1 - Descarga del PDF del certificado.
     *
     * No se enlaza el archivo directo desde storage: pasa por el Controlador para poder
     * resolver las excepciones del caso de uso y, sobre todo, para generar un nombre de
     * archivo seguro cuando el registro no tiene uno válido (CU 25.1 Excepción 4).
     */
    public function descargar(Certificado $certificado)
    {
        // CU 25.2 Excepciones 1 y 2: el certificado no tiene PDF cargado, o el archivo
        // referenciado en BD ya no está en disco.
        if (!$certificado->archivo_pdf || !Storage::disk('public')->exists($certificado->archivo_pdf)) {
            return redirect()
                ->route('public.certificaciones')
                ->with('doc_no_disponible', 'El documento solicitado no está disponible por el momento.');
        }

        // CU 25.1 Excepción 4: si el nombre almacenado no sirve, se construye uno seguro
        // a partir del código y el nombre de la normativa.
        $nombreSeguro = Str::slug($certificado->codigo . '-' . $certificado->nombre);
        if ($nombreSeguro === '') {
            $nombreSeguro = 'certificado-' . $certificado->id_certificado;
        }

        return Storage::disk('public')->download($certificado->archivo_pdf, $nombreSeguro . '.pdf');
    }
}
