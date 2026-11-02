<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // RNF23: el registro de errores guarda solo identificadores y códigos, nunca el mensaje de la excepción,
        // que puede traer correos, nombres o textos de los Visitantes (por ejemplo, el SQL con sus valores).
        // stop() reemplaza el registro por defecto de Laravel, que sí incluye el mensaje.
        $this->reportable(function (Throwable $e) {
            Log::error('Error registrado.', [
                'tipo' => get_class($e),
                'codigo' => $e->getCode(),
                'origen' => basename($e->getFile()) . ':' . $e->getLine(),
                'id_admin' => Auth::id(),
            ]);
        })->stop();

        $this->renderable(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'La carga supera el límite de 64 MB.'], 413);
            }

            return response()->view('errors.upload-too-large', [], 413);
        });
    }
}
