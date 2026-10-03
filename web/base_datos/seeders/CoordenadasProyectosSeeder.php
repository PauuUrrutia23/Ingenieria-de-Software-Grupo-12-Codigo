<?php

namespace Database\Seeders;

use App\Models\Proyecto;
use Illuminate\Database\Seeder;

/**
 * RF22 — coordenadas de ejemplo para el Mapa Interactivo de proyectos.
 *
 * Completa Latitud y Longitud con el centro aproximado de la comuna de cada proyecto, solo cuando
 * el proyecto aún no las tiene. Son referencias para visualizar el mapa: la ubicación exacta de cada
 * obra se ajusta desde el Panel de Gestión (Editar proyecto → Latitud / Longitud).
 *
 * Idempotente: nunca sobrescribe coordenadas ya registradas.
 */
class CoordenadasProyectosSeeder extends Seeder
{
    /** Centro aproximado de comunas con obras de ejemplo (comuna en minúsculas => [latitud, longitud]). */
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
