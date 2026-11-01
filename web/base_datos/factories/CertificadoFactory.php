<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Certificado;
use App\Models\Administrador;

class CertificadoFactory extends Factory
{
    protected $model = Certificado::class;

    public function definition()
    {
        return [
            'nombre' => $this->faker->sentence(3),
            'organismo' => $this->faker->company(),
            'fecha_emision' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'id_admin' => Administrador::factory(),
        ];
    }
}
