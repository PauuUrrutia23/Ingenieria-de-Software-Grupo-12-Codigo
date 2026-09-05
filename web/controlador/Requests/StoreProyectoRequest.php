<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

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
            'categoria' => 'required|string|max:50',
            // RF50 / CU 50.1: el proyecto solo tiene dos estados de visibilidad.
            'estado_publicacion' => 'required|in:borrador,publicado',
            'imagenes' => 'nullable|array|max:15',
            'imagenes.*' => 'image|mimes:jpeg,png,webp,jpg|max:5120',
        ];
    }
}