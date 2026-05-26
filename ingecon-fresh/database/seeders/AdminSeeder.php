<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Crea el administrador inicial de la plataforma Ingecon.
     * Usa Argon2id configurado en config/hashing.php.
     */
    public function run(): void
    {
        $existe = DB::table('administrador')
            ->where('correo', 'admin@ingecon.cl')
            ->exists();

        if ($existe) {
            $this->command->warn('El administrador admin@ingecon.cl ya existe. Se omite la inserción.');
            return;
        }

        DB::table('administrador')->insert([
            'correo'            => 'admin@ingecon.cl',
            'password_hash'     => Hash::make('Ingecon2024!'),
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'activo'            => true,
        ]);

        $this->command->info('Administrador inicial creado: admin@ingecon.cl');
    }
}
