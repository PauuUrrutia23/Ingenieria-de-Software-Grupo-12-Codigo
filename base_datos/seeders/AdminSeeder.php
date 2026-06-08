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

        DB::statement("INSERT INTO administrador (correo, password_hash, intentos_fallidos, bloqueado_hasta, activo) VALUES (?, ?, ?, ?, TRUE)", [
            'admin@ingecon.cl',
            Hash::make('Ingecon2024!'),
            0,
            null,
        ]);

        $this->command->info('Administrador inicial creado: admin@ingecon.cl');
    }
}
