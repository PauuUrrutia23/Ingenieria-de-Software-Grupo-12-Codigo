<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Database\Seeder;

class BannerContenidoSeeder extends Seeder
{
    public function run(): void
    {
        if (Contenido::where('seccion', 'banner')->exists()) {
            return;
        }

        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        Contenido::create([
            'seccion' => 'banner',
            'titulo' => 'Banner principal',
            'cuerpo' => 'Viviendas y galpones en madera, fabricados en serie',
            'archivo' => 'img/hero-panoramica.jpg',
            'tipo_mime' => 'image/jpeg',
            'orden' => 1,
            'activo' => true,
            'id_admin' => $admin->id_admin,
        ]);
    }
}
