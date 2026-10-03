<?php

namespace App\Support;

/**
 * RF18 — lista predefinida de Ubicaciones Geográficas (regiones de Chile, de norte a sur).
 *
 * Fuente única para el formulario de proyectos del Panel de gestión y su validación, de modo
 * que el filtro público por región nunca reciba variantes escritas a mano ("Biobio", "BioBío", ...).
 */
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
