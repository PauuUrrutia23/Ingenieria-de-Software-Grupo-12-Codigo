<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ImagenProyecto;

class ImagenProyectoFactory extends Factory
{
    protected $model = ImagenProyecto::class;

    public function definition()
    {
        return [
            'imagen' => 'proyectos/' . $this->faker->uuid() . '.jpg',
            'nombre_archivo' => $this->faker->word() . '.jpg',
            'tipo_mime' => 'image/jpeg',
        ];
    }
}
