<?php

namespace Database\Seeders;

use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

// migrate:fresh vacía la tabla pero no borra los archivos de storage: aquí se vuelven a enlazar.
class ImagenesProyectoSeeder extends Seeder
{
    public function run(): void
    {
        if (ImagenProyecto::count() > 0) {
            return;
        }

        $mapa = [
            'Conjunto Habitacional Los Aromos' => [
                ['los-aromos-01.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_57-7.png'],
                ['los-aromos-02.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_52-1.png'],
            ],
            'Bodega Agroindustrial Santa Filomena' => [
                ['santa-filomena-01.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_58-8.png'],
                ['santa-filomena-02.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_55-4.png'],
            ],
            'Escalera Prefabricada Edificio Vista Andes' => [
                ['vista-andes-01.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_54-3.png'],
                ['vista-andes-02.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_59-9.png'],
                ['vista-andes-03.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_35_55-4.png'],
            ],
            'Pabellón Industrial MultiAcero' => [
                ['multiacero-01.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_53-2.png'],
                ['multiacero-02.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_35_54-3.png'],
            ],
            'Vivienda Social DS19 Rauco' => [
                ['ds19-rauco-01.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_56-5.png'],
                ['ds19-rauco-02.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_35_53-2.png'],
            ],
            'Cerchas y Tabiques Planta CMPC' => [
                ['planta-cmpc-01.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_38_57-6.png'],
                ['planta-cmpc-02.jpg', 'Imagen de ChatGPT 1 oct 2026, 23_35_58-7.png'],
            ],
        ];

        foreach ($mapa as $nombreObra => $imagenes) {
            $proyecto = Proyecto::where('nombre_obra', $nombreObra)->first();
            if (!$proyecto) {
                continue;
            }

            foreach ($imagenes as [$archivo, $nombreOriginal]) {
                ImagenProyecto::create([
                    'imagen' => 'proyectos/' . $archivo,
                    'nombre_archivo' => $nombreOriginal,
                    'tipo_mime' => 'image/jpeg',
                    'id_proyecto' => $proyecto->id_proyecto,
                ]);
            }
        }
    }
}
