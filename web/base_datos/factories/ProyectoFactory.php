<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Proyecto;
use App\Models\Administrador;

class ProyectoFactory extends Factory
{
    protected $model = Proyecto::class;

    public function definition()
    {
        return [
            'nombre_obra' => 'Obra ' . $this->faker->unique()->words(2, true),
            'descripcion_tecnica' => $this->faker->sentence(),
            'region' => $this->faker->randomElement(['Metropolitana', 'Biobío', 'Maule', 'Araucanía']),
            'ubicacion_geografica' => $this->faker->city() . ', Metropolitana',
            'anio_ejecucion' => $this->faker->numberBetween(2020, 2026),
            'estado_publicacion' => 'publicado',
            'categoria' => $this->faker->randomElement(['construccion', 'industrial', 'terminaciones']),
            'id_admin' => Administrador::factory(),
        ];
    }
}
