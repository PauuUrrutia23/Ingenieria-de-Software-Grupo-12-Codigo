<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrador;
use App\Models\Proyecto;
use App\Models\Certificado;
use App\Models\Colaborador;
use App\Models\Contenido;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de ejemplo para que el sitio público no se vea vacío en desarrollo.
 * Sin fotografías reales: los Proyectos se muestran con el placeholder
 * "SIN IMAGEN" ya contemplado en las vistas (no requieren archivo).
 */
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
                ['nombre_obra' => 'Conjunto Habitacional Los Aromos', 'categoria' => 'construccion', 'region' => 'Metropolitana', 'ubicacion_geografica' => 'Melipilla, Metropolitana', 'anio_ejecucion' => 2025, 'descripcion_tecnica' => 'Viviendas industrializadas en pino radiata impregnado, montaje de cerchas tipo A.'],
                ['nombre_obra' => 'Bodega Agroindustrial Santa Filomena', 'categoria' => 'industrial', 'region' => 'Biobío', 'ubicacion_geografica' => 'Los Ángeles, Biobío', 'anio_ejecucion' => 2025, 'descripcion_tecnica' => 'Galpón de gran luz con conectores de acero galvanizado y estructura de madera laminada.'],
                ['nombre_obra' => 'Escalera Prefabricada Edificio Vista Andes', 'categoria' => 'terminaciones', 'region' => 'Metropolitana', 'ubicacion_geografica' => 'Puente Alto, Metropolitana', 'anio_ejecucion' => 2024, 'descripcion_tecnica' => 'Fabricación e instalación de escalera prefabricada en madera con terminación barnizada.'],
                ['nombre_obra' => 'Pabellón Industrial MultiAcero', 'categoria' => 'industrial', 'region' => 'Maule', 'ubicacion_geografica' => 'Talca, Maule', 'anio_ejecucion' => 2024, 'descripcion_tecnica' => 'Estructura de acopio con cerchas de gran luz y conectores metálicos de fabricación propia.'],
                ['nombre_obra' => 'Vivienda Social DS19 Rauco', 'categoria' => 'construccion', 'region' => 'Maule', 'ubicacion_geografica' => 'Rauco, Maule', 'anio_ejecucion' => 2024, 'descripcion_tecnica' => 'Panelería y volumetría prefabricada para conjunto habitacional bajo programa DS19.'],
                ['nombre_obra' => 'Cerchas y Tabiques Planta CMPC', 'categoria' => 'terminaciones', 'region' => 'Araucanía', 'ubicacion_geografica' => 'Pitrufquén, Araucanía', 'anio_ejecucion' => 2023, 'descripcion_tecnica' => 'Suministro de cerchas y tabiques con tecnología Finger-Joint.'],
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
            $logoPath = 'colaboradores/placeholder-logo.png';
            if (!Storage::disk('public')->exists($logoPath)) {
                $img = imagecreatetruecolor(240, 100);
                $bg = imagecolorallocate($img, 232, 230, 223);
                $fg = imagecolorallocate($img, 74, 74, 74);
                imagefill($img, 0, 0, $bg);
                imagestring($img, 4, 60, 42, 'LOGO', $fg);
                ob_start();
                imagepng($img);
                $data = ob_get_clean();
                imagedestroy($img);
                Storage::disk('public')->put($logoPath, $data);
            }

            foreach (['LP', 'MultiAcero', 'CMPC', 'Arauco'] as $nombre) {
                Colaborador::create([
                    'nombre_comercial' => $nombre,
                    'logotipo' => $logoPath,
                    'tipo_mime' => 'image/png',
                    'id_admin' => $admin->id_admin,
                ]);
            }
        }

        // RF10 / CU 10.1: la URL de la documentacion tecnica de Conectores Metalicos
        // vive en BD para poder cambiarla sin tocar el codigo.
        // PROVISIONAL: el valor definitivo lo tiene que confirmar Ingecon. El anterior
        // (simpsonstrongtie.cl) ni siquiera resuelve en DNS; este si carga y al menos
        // apunta a documentacion tecnica y no a la portada de un fabricante.
        if (Contenido::where('seccion', 'documentacion')->count() === 0) {
            Contenido::create([
                'seccion' => 'documentacion',
                'titulo' => 'Documentacion tecnica - Conectores Metalicos',
                'enlace' => env('DOCS_CONECTORES_URL', 'https://www.strongtie.com/literature'),
                'activo' => true,
                'orden' => 0,
                'id_admin' => $admin->id_admin,
            ]);
        }
    }
}
