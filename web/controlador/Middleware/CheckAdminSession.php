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

        // RF27/RF32: la autoridad es la fila "sesiones". Se resuelve por el token
        // guardado en datos de sesión (persiste entre peticiones) y se exige que la
        // fila activa pertenezca al admin autenticado. Sin token o sin fila vigente,
        // se cierra la sesión local y se expulsa al panel.
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