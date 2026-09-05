<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * FinfoValidator («Entity») — Diagrama de Componentes, capa Servicios.
 *
 * Validación de MIME real mediante la extensión nativa `finfo` de PHP (RNF04:
 * "verificar tipo, tamaño y formato de los archivos antes de su almacenamiento").
 * No basta con confiar en la extensión del nombre del archivo: un .exe renombrado
 * a .pdf pasa la validación por extensión pero falla acá, porque se inspeccionan
 * los bytes reales del archivo.
 */
class FinfoValidator
{
    /** MIME real del archivo según sus primeros bytes, o null si no se pudo determinar. */
    public function mimeReal(UploadedFile $archivo): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $archivo->getRealPath()) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        return $mime ?: null;
    }

    public function esPdf(UploadedFile $archivo): bool
    {
        if ($this->mimeReal($archivo) !== 'application/pdf') {
            return false;
        }

        return $this->tieneCabeceraPdf($archivo);
    }

    /** RNF06: todo PDF ISO 32000-1 comienza con la cabecera "%PDF-". */
    public function tieneCabeceraPdf(UploadedFile $archivo): bool
    {
        $manejador = @fopen($archivo->getRealPath(), 'rb');
        if ($manejador === false) {
            return false;
        }

        $cabecera = fread($manejador, 5);
        fclose($manejador);

        return $cabecera === '%PDF-';
    }

    public function esImagen(UploadedFile $archivo): bool
    {
        return str_starts_with((string) $this->mimeReal($archivo), 'image/');
    }
}
