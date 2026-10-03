<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrador;
use App\Models\Proyecto;
use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Contenido;

class EjemploDatosSeeder extends Seeder
{
    public function run()
    {
        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        if (Proyecto::count() === 0) {
            $proyectos = [
                ['nombre_obra' => 'Conjunto Habitacional Los Aromos', 'categoria' => 'construccion', 'region' => 'Metropolitana', 'comuna' => 'Melipilla', 'anio_ejecucion' => 2025, 'descripcion_tecnica' => 'Viviendas industrializadas en pino radiata impregnado, montaje de cerchas tipo A.'],
                ['nombre_obra' => 'Bodega Agroindustrial Santa Filomena', 'categoria' => 'industrial', 'region' => 'Biobío', 'comuna' => 'Los Ángeles', 'anio_ejecucion' => 2025, 'descripcion_tecnica' => 'Galpón de gran luz con conectores de acero galvanizado y estructura de madera laminada.'],
                ['nombre_obra' => 'Escalera Prefabricada Edificio Vista Andes', 'categoria' => 'terminaciones', 'region' => 'Metropolitana', 'comuna' => 'Puente Alto', 'anio_ejecucion' => 2024, 'descripcion_tecnica' => 'Fabricación e instalación de escalera prefabricada en madera con terminación barnizada.'],
                ['nombre_obra' => 'Pabellón Industrial MultiAcero', 'categoria' => 'industrial', 'region' => 'Maule', 'comuna' => 'Talca', 'anio_ejecucion' => 2024, 'descripcion_tecnica' => 'Estructura de acopio con cerchas de gran luz y conectores metálicos de fabricación propia.'],
                ['nombre_obra' => 'Vivienda Social DS19 Rauco', 'categoria' => 'construccion', 'region' => 'Maule', 'comuna' => 'Rauco', 'anio_ejecucion' => 2024, 'descripcion_tecnica' => 'Panelería y volumetría prefabricada para conjunto habitacional bajo programa DS19.'],
                ['nombre_obra' => 'Cerchas y Tabiques Planta CMPC', 'categoria' => 'terminaciones', 'region' => 'Araucanía', 'comuna' => 'Pitrufquén', 'anio_ejecucion' => 2023, 'descripcion_tecnica' => 'Suministro de cerchas y tabiques con tecnología Finger-Joint.'],
            ];

            foreach ($proyectos as $p) {
                Proyecto::create($p + ['estado_publicacion' => 'publicado', 'id_admin' => $admin->id_admin]);
            }
        }

        if (Certificado::count() === 0) {
            $certificados = [
                ['nombre' => 'Madera preservada - Pino radiata', 'organismo' => 'INN Chile'],
                ['nombre' => 'Control de calidad de la madera tratada', 'organismo' => 'COPROF Laboratorios'],
                ['nombre' => 'Madera - Construcciones - Cálculo', 'organismo' => 'INN Chile'],
            ];

            foreach ($certificados as $c) {
                Certificado::create($c + ['estado' => 'vigente', 'id_admin' => $admin->id_admin]);
            }
        }

        if (Colaborador::count() === 0) {
            foreach ([
                'LP' => 'img/colaboradores/lp-demo.png',
                'MultiAcero' => 'img/colaboradores/multiacero-demo.png',
                'CMPC' => 'img/colaboradores/cmpc-demo.png',
                'Arauco' => 'img/colaboradores/arauco-demo.png',
            ] as $nombre => $logoPath) {
                Colaborador::create([
                    'nombre_comercial' => $nombre,
                    'logotipo' => $logoPath,
                    'tipo_mime' => 'image/png',
                    'id_admin' => $admin->id_admin,
                ]);
            }
        }

        if (Contenido::where('seccion', 'documentacion')->count() === 0) {
            Contenido::create([
                'seccion' => 'documentacion',
                'titulo' => 'Ficha técnica Conectores Metálicos (documento de demostración)',
                'enlace' => env('DOCS_CONECTORES_URL', EnlacesInstitucionalesSeeder::DOC_DEMO),
                'activo' => true,
                'orden' => 0,
                'id_admin' => $admin->id_admin,
            ]);
        }
    }
}
