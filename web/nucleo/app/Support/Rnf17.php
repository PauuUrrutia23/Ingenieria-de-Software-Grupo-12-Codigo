<?php

namespace App\Support;

// Límites y formatos de archivos (RNF17) en un solo lugar.
final class Rnf17
{
    public const LOGO_MAX_KB = 500;

    public const IMAGEN_MAX_KB = 5120;

    public const CONTENIDO_IMAGEN_MAX_KB = 5120;

    public const CONTENIDO_VIDEO_MAX_KB = 51200;

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

    // Valida el contenido real del archivo (JPG o PNG). El MIME que reporta finfo
    // varía según la cámara o el programa que generó el JPG (image/jpeg, image/pjpeg,
    // image/mpo...), por eso se revisa la firma de la imagen y no solo el MIME.
    public static function reglaJpgPng(): \Closure
    {
        return function ($attribute, $archivo, $fail) {
            if (!$archivo instanceof \Illuminate\Http\UploadedFile || !$archivo->isValid()) {
                return;
            }
            $info = @getimagesize($archivo->getRealPath());
            if ($info === false || !in_array($info[2] ?? null, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                $fail('El archivo debe ser una imagen JPG o PNG válida.');
            }
        };
    }

    public static function reglasImagen(): array
    {
        return ['file', self::reglaJpgPng(), 'max:' . self::IMAGEN_MAX_KB];
    }

    public static function reglasLogo(): array
    {
        return ['file', self::reglaJpgPng(), 'max:' . self::LOGO_MAX_KB];
    }

    public static function reglasContenido(): array
    {
        return [
            'file',
            'mimetypes:image/jpeg,image/png,image/webp,video/mp4',
            'max:' . self::CONTENIDO_VIDEO_MAX_KB,
            function ($attribute, $archivo, $fail) {
                if ($archivo instanceof \Illuminate\Http\UploadedFile
                    && $archivo->isValid()
                    && $archivo->getMimeType() !== 'video/mp4'
                    && $archivo->getSize() > self::CONTENIDO_IMAGEN_MAX_KB * 1024) {
                    $fail('La imagen no puede superar los 5 MB.');
                }
            },
        ];
    }

    public static function mensajeLimite(int $kb): string
    {
        if ($kb % 1024 === 0) {
            return ($kb / 1024) . ' MB';
        }

        return $kb . ' KB';
    }
}
