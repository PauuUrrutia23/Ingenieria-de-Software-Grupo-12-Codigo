<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdminSession
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login')->withErrors(['Acceso denegado. Por favor inicie sesión.']);
        }

        $admin = Auth::user();
        if (!$admin->activo) {
            Auth::logout();
            return redirect('/login')->withErrors(['Su cuenta ha sido desactivada.']);
        }

        return $next($request);
    }
}