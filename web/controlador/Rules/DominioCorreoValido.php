<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DominioCorreoValido implements ValidationRule
{
    private const DOMINIO_SONDA = 'gmail.com';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !str_contains($value, '@')) {
            return;
        }

        $dominio = substr(strrchr($value, '@'), 1);
        if ($dominio === '' || !function_exists('checkdnsrr')) {
            return;
        }

        if ($this->resuelve($dominio)) {
            return;
        }

        if (!$this->resuelve(self::DOMINIO_SONDA)) {
            return;
        }

        $fail('El dominio del correo electrónico no existe. Ingrese una dirección válida.');
    }

    private function resuelve(string $dominio): bool
    {
        return @checkdnsrr($dominio, 'MX')
            || @checkdnsrr($dominio, 'A')
            || @checkdnsrr($dominio, 'AAAA');
    }
}
