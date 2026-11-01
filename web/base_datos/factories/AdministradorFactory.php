<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;

class AdministradorFactory extends Factory
{
    protected $model = Administrador::class;

    public function definition()
    {
        return [
            'correo' => $this->faker->unique()->safeEmail(),
            'password_hash' => Hash::make('Password1!'),
            'rol' => 'admin',
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'activo' => true,
        ];
    }

    // RF59: cuenta con el permiso Editar Contenido.
    public function editorContenido(): static
    {
        return $this->state(['perm_editar_contenido' => true]);
    }

    // RF42: cuenta con el permiso Exportar Consultas.
    public function exportadorConsultas(): static
    {
        return $this->state(['perm_exportar_consultas' => true]);
    }
}
