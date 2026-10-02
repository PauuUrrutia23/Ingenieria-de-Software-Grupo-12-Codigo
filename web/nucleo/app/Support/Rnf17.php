<?php

namespace App\Support;

/**
 * FASE 12 — fuente única de verdad de las reglas RNF17 de archivos.
 *
 * Centraliza límites y formatos para que ningún controlador defina su propio tope.
 * Válido: sólo JPG/JPEG/PNG. Quedan explícitamente excluidos WebP y MP4 para los RF
 * cubiertos por RNF17 vigente (logotipos ≤500 KB, imágenes ≤2 MB).
 */
final class Rnf17
{
    public const LOGO_MAX_KB = 500;

    public const IMAGEN_MAX_KB = 2048;

    /** Extensiones aceptadas (sin punto). No se incluyen webp ni mp4. */
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

    /** Reglas de validación para una imagen de obra (≤2 MB, JPG/JPEG/PNG). */
    public static function reglasImagen(): array
    {
        return ['file', 'mimes:' . self::mimesImagen(), 'max:' . self::IMAGEN_MAX_KB];
    }

    /** Reglas de validación para un logotipo (≤500 KB, JPG/JPEG/PNG). */
    public static function reglasLogo(): array
    {
        return ['file', 'mimes:' . self::mimesImagen(), 'max:' . self::LOGO_MAX_KB];
    }

    /**
     * Construye un mensaje de límite legible a partir de los kB definidos aquí,
     * de modo que vistas y controladores citen siempre el mismo tope.
     */
    public static function mensajeLimite(int $kb): string
    {
        if ($kb % 1024 === 0) {
            return ($kb / 1024) . ' MB';
        }

        return $kb . ' KB';
    }
}
