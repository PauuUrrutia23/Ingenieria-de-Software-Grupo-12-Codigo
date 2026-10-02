<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Database\Seeder;

/** Conserva en BD las tres etapas que antes estaban escritas en la vista pública. */
class FasesIndustrialesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        $etapas = [
            ['Descortezado', 'Preparación de la madera en rollizos.', 'img/proceso-descortezado.jpg'],
            ['Impregnación Vacío-Presión', 'Tratamiento con preservantes para durabilidad estructural.', 'img/proceso-impregnacion.jpg'],
            ['Ensamblado', 'Armado de cerchas, paneles y secciones.', 'img/proceso-ensamblado.jpg'],
        ];

        foreach ($etapas as $indice => [$nombre, $descripcion, $imagen]) {
            Contenido::firstOrCreate(
                ['seccion' => 'fases_industriales', 'titulo' => $nombre],
                [
                    'cuerpo' => $descripcion,
                    'archivo' => $imagen,
                    'tipo_mime' => 'image/jpeg',
                    'orden' => $indice + 1,
                    'activo' => true,
                    'id_admin' => $admin->id_admin,
                ]
            );
        }
    }
}
