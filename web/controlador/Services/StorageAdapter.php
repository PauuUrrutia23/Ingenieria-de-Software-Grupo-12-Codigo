<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * StorageAdapter («Entity») — Diagrama de Componentes, capa Servicios.
 *
 * Punto único de acceso al disco "public" de storage/ para guardar y borrar los
 * archivos que suben los controladores del Panel de Gestión (fotografías de
 * proyectos, logotipos de colaboradores, imágenes/PDF de certificados, contenido
 * multimedia). Los archivos viven en el filesystem, no en la Base de Datos — solo
 * se persiste la ruta.
 */
class StorageAdapter
{
    private const DISCO = 'public';

    /** Guarda un archivo subido en la carpeta indicada y devuelve la ruta relativa. */
    public function guardar(UploadedFile $archivo, string $carpeta): string
    {
        return $archivo->store($carpeta, self::DISCO);
    }

    /** Borra un archivo por su ruta relativa (no falla si ya no existe). */
    public function borrar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk(self::DISCO)->delete($ruta);
        }
    }

    /** Reemplaza un archivo existente: borra el anterior (si hay) y guarda el nuevo. */
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

    public function url(string $ruta): string
    {
        return Storage::disk(self::DISCO)->url($ruta);
    }
}
