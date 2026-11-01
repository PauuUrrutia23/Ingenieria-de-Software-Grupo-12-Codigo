<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// RNF03: el permiso se verifica en el servidor, no basta con ocultar la opción. Debe ir después de CheckAdminSession.
class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $permiso)
    {
        $admin = $request->user();

        if (!$admin || !$admin->puede($permiso)) {
            abort(403, 'Su cuenta no tiene el permiso necesario para esta acción.');
        }

        return $next($request);
    }
}
