<?php

namespace App\Support;

final class RegionesChile
{
    public const LISTA = [
        'Arica y Parinacota',
        'Tarapacá',
        'Antofagasta',
        'Atacama',
        'Coquimbo',
        'Valparaíso',
        'Metropolitana',
        "O'Higgins",
        'Maule',
        'Ñuble',
        'Biobío',
        'Araucanía',
        'Los Ríos',
        'Los Lagos',
        'Aysén',
        'Magallanes',
    ];

    public static function valores(): array
    {
        return self::LISTA;
    }
}
