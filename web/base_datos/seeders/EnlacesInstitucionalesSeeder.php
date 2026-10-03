<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Database\Seeder;

// La ficha técnica es un PDF de demostración y la ubicación es provisional: reemplazar por los datos reales de Ingecon.
class EnlacesInstitucionalesSeeder extends Seeder
{
    public const DOC_DEMO = '/docs/conectores-metalicos-ficha-tecnica-demo.pdf';
    public const UBICACION_PROVISIONAL = 'https://www.google.com/maps/search/?api=1&query=Ingecon+Chile';

    public function run(): void
    {
        $admin = Administrador::first();
        if (!$admin) {
            return;
        }

        $doc = Contenido::where('seccion', 'documentacion')->orderByDesc('id_contenido')->first();
        if (!$doc) {
            Contenido::create([
                'seccion' => 'documentacion',
                'titulo' => 'Ficha técnica Conectores Metálicos (documento de demostración)',
                'enlace' => self::DOC_DEMO,
                'activo' => true,
                'orden' => 0,
                'id_admin' => $admin->id_admin,
            ]);
        } elseif (!$doc->enlace || str_contains($doc->enlace, 'strongtie.com')) {
            $doc->update([
                'titulo' => 'Ficha técnica Conectores Metálicos (documento de demostración)',
                'enlace' => self::DOC_DEMO,
            ]);
        }

        if (!Contenido::where('seccion', 'ubicacion')->exists()) {
            Contenido::create([
                'seccion' => 'ubicacion',
                'titulo' => 'Ubicación provisional: reemplazar por la dirección real de la planta',
                'enlace' => self::UBICACION_PROVISIONAL,
                'activo' => true,
                'orden' => 0,
                'id_admin' => $admin->id_admin,
            ]);
        }
    }
}
