<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FinfoValidator
{
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
