<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Consulta;
use App\Models\Visitante;

class ConsultaFactory extends Factory
{
    protected $model = Consulta::class;

    public function definition()
    {
        return [
            'id_visitante' => Visitante::factory(),
            'mensaje' => $this->faker->paragraph(),
            'created_at' => now(),
            'estado' => 'pendiente',
        ];
    }
}
