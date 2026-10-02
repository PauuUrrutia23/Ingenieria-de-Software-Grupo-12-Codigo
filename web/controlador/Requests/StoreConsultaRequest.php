<?php

namespace App\Http\Requests;

use App\Rules\DominioCorreoValido;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'apellido' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\s]+$/u'],
            'email' => ['required', 'email:rfc', 'max:150', new DominioCorreoValido()],
            'mensaje' => ['required', 'string', 'min:10', 'max:1000'],
            'acepta_terminos' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'apellido.regex' => 'El apellido solo puede contener letras y espacios.',
            'mensaje.min' => 'El mensaje debe tener al menos 10 caracteres.',
            'mensaje.max' => 'El mensaje no puede superar los 1000 caracteres.',
            'email.email' => 'Ingrese un correo electrónico válido.',
        ];
    }
}
