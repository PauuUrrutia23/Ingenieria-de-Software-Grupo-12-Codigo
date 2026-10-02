<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Producto;
use Illuminate\Database\Seeder;

/** Traslada al modelo Producto las tres líneas que antes se mostraban en HTML estático. */
class ProductosPublicosSeeder extends Seeder
{
    public function run(): void
    {
        if (Producto::query()->exists()) {
            return;
        }

        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        foreach ([
            ['Vivienda industrializada', 'Paneles y volumetría prefabricada para conjuntos habitacionales.', 'img/linea-vivienda-industrializada.jpg'],
            ['Pabellones y galpones', 'Estructuras de gran luz para uso industrial y agroindustrial.', 'img/linea-pabellon-gran-luz.jpg'],
            ['Terminaciones y servicios', 'Escaleras prefabricadas, cerchas y elementos a medida.', 'img/linea-terminaciones.jpg'],
        ] as [$nombre, $descripcion, $imagen]) {
            Producto::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'imagen' => $imagen,
                'tipo_mime' => 'image/jpeg',
                'id_admin' => $admin->id_admin,
            ]);
        }
    }
}
