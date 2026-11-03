<?php

namespace Database\Seeders;

use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

// Fotos de los proyectos de ejemplo. Si el archivo aún no está en storage (instalación limpia), se copia
// desde las fotos que trae el sitio en public/img; si ya existe (migrate:fresh), solo se vuelve a enlazar.
class ImagenesProyectoSeeder extends Seeder
{
    public function run(): void
    {
        if (ImagenProyecto::count() > 0) {
            return;
        }

        $mapa = [
            'Conjunto Habitacional Los Aromos' => [
                ['los-aromos-01.jpg', 'linea-vivienda-industrializada.jpg'],
                ['los-aromos-02.jpg', 'proceso-ensamblado.jpg'],
            ],
            'Bodega Agroindustrial Santa Filomena' => [
                ['santa-filomena-01.jpg', 'linea-pabellon-gran-luz.jpg'],
                ['santa-filomena-02.jpg', 'hero-panoramica.jpg'],
            ],
            'Escalera Prefabricada Edificio Vista Andes' => [
                ['vista-andes-01.jpg', 'linea-terminaciones.jpg'],
                ['vista-andes-02.jpg', 'conector-instalado-nudo.jpg'],
                ['vista-andes-03.jpg', 'proceso-ensamblado.jpg'],
            ],
            'Pabellón Industrial MultiAcero' => [
                ['multiacero-01.jpg', 'linea-pabellon-gran-luz.jpg'],
                ['multiacero-02.jpg', 'conector-cercha-planta.jpg'],
            ],
            'Vivienda Social DS19 Rauco' => [
                ['ds19-rauco-01.jpg', 'linea-vivienda-industrializada.jpg'],
                ['ds19-rauco-02.jpg', 'proceso-impregnacion.jpg'],
            ],
            'Cerchas y Tabiques Planta CMPC' => [
                ['planta-cmpc-01.jpg', 'conector-cercha-planta.jpg'],
                ['planta-cmpc-02.jpg', 'proceso-descortezado.jpg'],
            ],
        ];

        $disco = Storage::disk('public');
        foreach ($mapa as $nombreObra => $imagenes) {
            $proyecto = Proyecto::where('nombre_obra', $nombreObra)->first();
            if (!$proyecto) {
                continue;
            }

            foreach ($imagenes as [$archivo, $fotoDelSitio]) {
                $ruta = 'proyectos/' . $archivo;
                if (!$disco->exists($ruta)) {
                    $origen = public_path('img/' . $fotoDelSitio);
                    if (!is_file($origen)) {
                        continue;
                    }
                    $disco->put($ruta, file_get_contents($origen));
                }

                ImagenProyecto::create([
                    'imagen' => $ruta,
                    'nombre_archivo' => $archivo,
                    'tipo_mime' => 'image/jpeg',
                    'id_proyecto' => $proyecto->id_proyecto,
                ]);
            }
        }
    }
}
