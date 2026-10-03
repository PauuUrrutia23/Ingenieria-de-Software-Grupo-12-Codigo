<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Database\Seeder;

class ProductoContenidoSeeder extends Seeder
{
    public function run(): void
    {
        if (Contenido::where('seccion', 'ficha_conectores')->exists()) {
            return;
        }

        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        $json = fn (array $datos) => json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $filas = [
            [
                'titulo' => 'descripcion',
                'orden' => 1,
                'cuerpo' => 'Placas y conectores de acero para el ensamble de cerchas, entramados y estructuras de madera. Fabricados con acero de proveedores certificados y dimensionados según el cálculo de cada proyecto.',
            ],
            [
                'titulo' => 'especificaciones',
                'orden' => 2,
                'cuerpo' => $json([
                    ['label' => 'Material', 'value' => 'Acero estructural galvanizado'],
                    ['label' => 'Espesor', 'value' => '1.5 - 3.0 mm'],
                    ['label' => 'Tratamiento superficial', 'value' => 'Galvanizado en caliente'],
                    ['label' => 'Formatos disponibles', 'value' => 'Según cálculo estructural del proyecto'],
                    ['label' => 'Normativa aplicable', 'value' => 'ANSI/TPI, NCh 1198'],
                ]),
            ],
            [
                'titulo' => 'aplicaciones',
                'orden' => 3,
                'cuerpo' => $json([
                    ['icono' => 'home', 'titulo' => 'Cerchas de techumbre', 'texto' => 'Unión de elementos en cerchas fabricadas en serie.'],
                    ['icono' => 'building-2', 'titulo' => 'Entramados de muro', 'texto' => 'Fijación de paneles en vivienda industrializada.'],
                    ['icono' => 'factory', 'titulo' => 'Estructuras de gran luz', 'texto' => 'Nudos de pabellones y galpones industriales.'],
                ]),
            ],
            [
                'titulo' => 'imagen_principal',
                'orden' => 4,
                'archivo' => 'img/conector-pieza-sola.jpg',
                'cuerpo' => 'Conector metálico galvanizado en forma de U, con alas perforadas para clavos y base atornillable, sobre fondo blanco',
            ],
            [
                'titulo' => 'miniaturas',
                'orden' => 5,
                'cuerpo' => $json([
                    ['src' => 'img/conector-familia.jpg', 'alt' => 'Familia de conectores galvanizados: apoyos para viga, escuadras, placa ranurada y base de pilar'],
                    ['src' => 'img/conector-instalado-nudo.jpg', 'alt' => 'Conector galvanizado atornillado con pernos en el nudo entre una columna y dos vigas de madera laminada'],
                    ['src' => 'img/conector-cercha-planta.jpg', 'alt' => 'Cerchas de madera terminadas con placas metálicas dentadas en los nudos, apiladas en la nave de fabricación'],
                    ['src' => 'img/proceso-ensamblado.jpg', 'alt' => 'Placa conectora metálica fijada con clavadora neumática al nudo de una cercha sobre la mesa de armado'],
                ]),
            ],
        ];

        foreach ($filas as $f) {
            Contenido::create([
                'seccion' => 'ficha_conectores',
                'titulo' => $f['titulo'],
                'cuerpo' => $f['cuerpo'],
                'archivo' => $f['archivo'] ?? null,
                'activo' => true,
                'orden' => $f['orden'],
                'id_admin' => $admin->id_admin,
            ]);
        }
    }
}
