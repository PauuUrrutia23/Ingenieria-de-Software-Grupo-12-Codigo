<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * RF52/RF53 — restringe rutas de gestión de administradores al Administrador Jefe.
 * Debe encadenarse DESPUÉS de CheckAdminSession: asume un admin autenticado y con
 * sesión vigente. Si el rol no es admin_jefe, aborta 403 sin ejecutar el controlador.
 */
class CheckAdminJefe
{
    public function handle(Request $request, Closure $next)
    {
        $admin = $request->user();

        if (!$admin || $admin->rol !== 'admin_jefe') {
            abort(403, 'Acceso restringido al Administrador Jefe.');
        }

        return $next($request);
    }
}
