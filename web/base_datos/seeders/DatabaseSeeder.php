<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            AdminJefeSeeder::class,
            EjemploDatosSeeder::class,
            ImagenesProyectoSeeder::class,
            ProductoContenidoSeeder::class,
            FasesIndustrialesSeeder::class,
            ProductosPublicosSeeder::class,
            BannerContenidoSeeder::class,
            ContenidoPublicoSeeder::class,
            EnlacesInstitucionalesSeeder::class,
            CoordenadasProyectosSeeder::class,
        ]);
    }
}
