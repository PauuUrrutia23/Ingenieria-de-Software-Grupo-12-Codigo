<?php

namespace App\Http\Middleware;

use App\Models\Sesion;
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

        // El token de la sesión debe tener una fila activa en "sesiones" para este administrador.
        $token = $request->session()->get('sesion_ingecon');

        $sesionVigente = $token !== null && Sesion::where('id_admin', $admin->id_admin)
            ->where('estado', 'activa')
            ->where('token_hash', hash('sha256', $token))
            ->exists();

        if (!$sesionVigente) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Auth::logout();

            return redirect('/login')->withErrors(['Acceso denegado. Por favor inicie sesión.']);
        }

        return $next($request);
    }
}