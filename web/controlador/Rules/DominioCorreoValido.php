<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * CU 1.1 Excepción 4 - "Dominio de correo inválido → rechaza la transacción".
 *
 * La Excepción 5 (formato) ya la cubre la regla `email` de Laravel; esta comprueba
 * que el dominio realmente exista y pueda recibir correo: primero registro MX y,
 * si no lo tiene, registro A/AAAA (muchos dominios reciben correo en el propio host).
 *
 * Diferencia clave con `email:dns` de Laravel: si el propio servicio de DNS no está
 * disponible (servidor sin red, demo offline), esta regla **no** rechaza el correo.
 * Rechazar todo por una caída de infraestructura dejaría el Formulario de Contacto
 * inutilizable, que es peor que aceptar un dominio dudoso.
 */
class DominioCorreoValido implements ValidationRule
{
    /** Dominio de control para saber si la resolución DNS está operativa. */
    private const DOMINIO_SONDA = 'gmail.com';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !str_contains($value, '@')) {
            return; // el formato lo valida la regla `email`; acá no se duplica el error
        }

        $dominio = substr(strrchr($value, '@'), 1);
        if ($dominio === '' || !function_exists('checkdnsrr')) {
            return;
        }

        if ($this->resuelve($dominio)) {
            return;
        }

        // El dominio no resolvió: ¿es que no existe, o es que no hay DNS?
        if (!$this->resuelve(self::DOMINIO_SONDA)) {
            return; // DNS caído: no se castiga al Visitante por un problema del servidor
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
