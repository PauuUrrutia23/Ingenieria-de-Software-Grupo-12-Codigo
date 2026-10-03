<?php

namespace Database\Seeders;

use App\Models\Proyecto;
use Illuminate\Database\Seeder;

// Coordenadas aproximadas (centro de la comuna), solo de ejemplo para el mapa.
class CoordenadasProyectosSeeder extends Seeder
{
    private const COMUNAS = [
        'melipilla' => [-33.6891, -71.2153],
        'los ángeles' => [-37.4697, -72.3537],
        'puente alto' => [-33.6117, -70.5758],
        'talca' => [-35.4264, -71.6554],
        'rauco' => [-34.9290, -71.3180],
        'pitrufquén' => [-38.9858, -72.6386],
        'santiago' => [-33.4489, -70.6693],
        'concepción' => [-36.8270, -73.0503],
        'temuco' => [-38.7359, -72.5904],
        'coronel' => [-37.0167, -73.1500],
    ];

    public function run(): void
    {
        Proyecto::query()->whereNull('latitud')->orWhereNull('longitud')->get()
            ->each(function (Proyecto $proyecto) {
                $coordenadas = self::COMUNAS[mb_strtolower(trim((string) $proyecto->comuna))] ?? null;
                if ($coordenadas) {
                    $proyecto->update(['latitud' => $coordenadas[0], 'longitud' => $coordenadas[1]]);
                }
            });
    }
}
