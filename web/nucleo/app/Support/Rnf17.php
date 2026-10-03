<?php

namespace App\Support;

// Límites y formatos de archivos (RNF17) en un solo lugar.
final class Rnf17
{
    public const LOGO_MAX_KB = 500;

    public const IMAGEN_MAX_KB = 2048;

    public const MIMES_IMAGEN = ['jpg', 'jpeg', 'png'];

    public static function logoMaxKb(): int
    {
        return self::LOGO_MAX_KB;
    }

    public static function imagenMaxKb(): int
    {
        return self::IMAGEN_MAX_KB;
    }

    public static function mimesImagen(): string
    {
        return implode(',', self::MIMES_IMAGEN);
    }

    public static function reglasImagen(): array
    {
        return ['file', 'mimes:' . self::mimesImagen(), 'max:' . self::IMAGEN_MAX_KB];
    }

    public static function reglasLogo(): array
    {
        return ['file', 'mimes:' . self::mimesImagen(), 'max:' . self::LOGO_MAX_KB];
    }

    public static function mensajeLimite(int $kb): string
    {
        if ($kb % 1024 === 0) {
            return ($kb / 1024) . ' MB';
        }

        return $kb . ' KB';
    }
}
