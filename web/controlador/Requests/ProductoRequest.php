<?php

namespace App\Http\Requests;

use App\Support\Rnf17;
use Illuminate\Foundation\Http\FormRequest;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string'],
            'imagen' => array_merge([$this->isMethod('post') ? 'required' : 'nullable'], Rnf17::reglasImagen()),
            'componentes' => ['nullable', 'array'],
            'componentes.*' => ['required', 'string', 'max:120'],
        ];
    }
}
