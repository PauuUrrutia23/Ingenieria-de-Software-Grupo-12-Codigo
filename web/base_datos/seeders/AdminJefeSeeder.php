<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;

class AdminJefeSeeder extends Seeder
{
    public function run()
    {
        Administrador::updateOrCreate(
            ['correo' => 'admin@ingecon.cl'],
            [
                'password_hash' => Hash::make('Admin123!'),
                'rol' => 'admin_jefe',
                'activo' => true,
                'intentos_fallidos' => 0
            ]
        );
    }
}
