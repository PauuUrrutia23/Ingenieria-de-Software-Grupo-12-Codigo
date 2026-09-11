<?php

namespace App\Rules;

use App\Services\FinfoValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

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

        if ($mime !== null && $mime !== 'application/pdf') {
            $fail('El archivo adjunto no es un documento PDF válido.');
            return;
        }

        if (!$validador->tieneCabeceraPdf($value)) {
            $fail('El archivo adjunto está dañado o no cumple el formato PDF estándar.');
        }
    }
}
