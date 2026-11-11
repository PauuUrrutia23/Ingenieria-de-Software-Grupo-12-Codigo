<?php

namespace App\Http\Middleware;

use App\Services\SitioEnConstruccion;
use Closure;
use Illuminate\Http\Request;

// Subida al hosting: muestra la página en construcción a los visitantes mientras esté activa.
class MostrarSitioEnConstruccion
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() && $this->enConstruccion()) {
            return response()->view('construccion');
        }

        return $next($request);
    }

    private function enConstruccion(): bool
    {
        try {
            return SitioEnConstruccion::activo();
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}
