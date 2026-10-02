<?php

namespace App\Support;

final class CategoriasProyecto
{
    public const ETIQUETAS = [
        'construccion' => 'Construcción / Vivienda industrializada',
        'industrial' => 'Industrial / Pabellones y galpones',
        'terminaciones' => 'Terminaciones y servicios',
    ];

    public static function valores(): array
    {
        return array_keys(self::ETIQUETAS);
    }

    public static function etiqueta(?string $valor): string
    {
        return self::ETIQUETAS[$valor] ?? (string) $valor;
    }
}
