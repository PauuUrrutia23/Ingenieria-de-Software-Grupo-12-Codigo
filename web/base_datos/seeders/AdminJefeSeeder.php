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
            // Subida al hosting: el correo se define en CORREO_ADMIN_JEFE del .env.
            ['correo' => config('app.correo_admin_jefe')],
            [
                'password_hash' => Hash::make('Admin123!'),
                'rol' => 'admin_jefe',
                'activo' => true,
                'intentos_fallidos' => 0
            ]
        );
    }
}
