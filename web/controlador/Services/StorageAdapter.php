<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StorageAdapter
{
    private const DISCO = 'public';

    public function guardar(UploadedFile $archivo, string $carpeta): string
    {
        return $archivo->store($carpeta, self::DISCO);
    }

    public function borrar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }

    public function reemplazar(?string $rutaAnterior, UploadedFile $archivoNuevo, string $carpeta): string
    {
        $this->borrar($rutaAnterior);

        return $this->guardar($archivoNuevo, $carpeta);
    }

    public function existe(?string $ruta): bool
    {
        return $ruta !== null && Storage::disk(self::DISCO)->exists($ruta);
    }

    public function descargar(string $ruta, string $nombreDescarga)
    {
        return Storage::disk(self::DISCO)->download($ruta, $nombreDescarga);
    }

    public function responderInline(string $ruta, string $nombreSeguro)
    {
        $absoluta = Storage::disk(self::DISCO)->path($ruta);

        return response()->file($absoluta, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreSeguro . '"',
        ]);
    }

    // Ruta relativa (/storage/...): si APP_URL no coincide con la dirección real del sitio, las imágenes igual cargan.
    public function url(string $ruta): string
    {
        return parse_url(Storage::disk(self::DISCO)->url($ruta), PHP_URL_PATH);
    }
}
