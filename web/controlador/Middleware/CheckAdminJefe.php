<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Debe ir después de CheckAdminSession.
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
