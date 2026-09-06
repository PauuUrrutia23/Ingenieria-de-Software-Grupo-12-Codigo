<?php
namespace App\Rules;

use App\Services\FinfoValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * RNF04 - Validación de documentos PDF.
 *
 * No basta con confiar en la extensión del archivo: FinfoValidator (Diagrama de
 * Componentes, capa Servicios) verifica el MIME real con la extensión nativa
 * `finfo` de PHP y además la cabecera del archivo (`%PDF-`), que es lo que define
 * un PDF conforme al formato estándar. Un .exe renombrado a .pdf pasa la validación por
 * extensión pero falla acá. Esta Rule es solo el punto de entrada que usa Laravel
 * en las validaciones de Request; la lógica real vive en FinfoValidator.
 */
class PdfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('El documento adjunto no pudo procesarse. Intente nuevamente.');
            return;
        }

        $validador = new FinfoValidator();
        $mime = $validador->mimeReal($value);

        // Si finfo no está disponible en el servidor, mimeReal() devuelve null y se
        // omite este chequeo (queda igual la verificación de cabecera de abajo).
        if ($mime !== null && $mime !== 'application/pdf') {
            $fail('El archivo adjunto no es un documento PDF válido.');
            return;
        }

        if (!$validador->tieneCabeceraPdf($value)) {
            $fail('El archivo adjunto está dañado o no cumple el formato PDF estándar.');
        }
    }
}
