<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * RNF04 / RNF06 - Validación de documentos PDF.
 *
 * No basta con confiar en la extensión del archivo: se verifica el MIME real con
 * la extensión nativa `finfo` de PHP y además la cabecera del archivo (`%PDF-`),
 * que es lo que define un PDF conforme a ISO 32000-1. Un .exe renombrado a .pdf
 * pasa la validación por extensión pero falla acá.
 */
class PdfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('El documento adjunto no pudo procesarse. Intente nuevamente.');
            return;
        }

        $ruta = $value->getRealPath();

        // 1) MIME real declarado por finfo, no por el nombre del archivo.
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $ruta) : null;
            if ($finfo) {
                finfo_close($finfo);
            }

            if ($mime !== 'application/pdf') {
                $fail('El archivo adjunto no es un documento PDF válido.');
                return;
            }
        }

        // 2) Cabecera del archivo: todo PDF ISO 32000-1 comienza con "%PDF-".
        $manejador = @fopen($ruta, 'rb');
        if ($manejador === false) {
            $fail('El archivo adjunto no pudo leerse. Intente nuevamente.');
            return;
        }

        $cabecera = fread($manejador, 5);
        fclose($manejador);

        if ($cabecera !== '%PDF-') {
            $fail('El archivo adjunto está dañado o no cumple el formato PDF estándar.');
        }
    }
}
