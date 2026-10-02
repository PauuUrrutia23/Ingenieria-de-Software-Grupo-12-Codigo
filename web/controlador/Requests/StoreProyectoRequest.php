<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use App\Support\Rnf17;
use App\Support\CategoriasProyecto;
use Illuminate\Validation\Rule;

class StoreProyectoRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'nombre_obra' => 'required|string|max:150',
            'descripcion_tecnica' => 'required|string',
            'region' => 'required|string|max:80',
            'ubicacion_geografica' => 'required|string|max:150',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'anio_ejecucion' => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'categoria' => ['required', Rule::in(CategoriasProyecto::valores())],

            // RF48 (Fase 22): el estado inicial NO viene de la vista; store() fuerza 'borrador'.
            // Fase 23: el alta exige entre 1 y 15 imágenes (obligatoriedad separada del estado).
            'imagenes' => 'required|array|min:1|max:15',
            'imagenes.*' => Rnf17::reglasImagen(),
        ];
    }
}
