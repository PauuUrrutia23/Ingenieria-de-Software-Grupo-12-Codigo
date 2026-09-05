<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Colaborador;
use App\Models\Administrador;

class ColaboradorFactory extends Factory
{
    protected $model = Colaborador::class;

    public function definition()
    {
        return [
            'nombre_comercial' => $this->faker->company(),
            'logotipo' => 'colaboradores/placeholder-logo.png',
            'tipo_mime' => 'image/png',
            'id_admin' => Administrador::factory(),
        ];
    }
}
