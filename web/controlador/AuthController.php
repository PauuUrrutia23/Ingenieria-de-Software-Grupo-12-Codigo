<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Administrador;
use App\Models\Sesion;
use App\Mail\CuentaBloqueadaMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'password' => 'required'
        ]);

        $admin = Administrador::where('correo', $request->correo)->first();

        if (!$admin) {
            return back()->withErrors(['correo' => 'Credenciales inválidas.']);
        }

        if (!$admin->activo) {
            return back()->withErrors(['correo' => 'Cuenta inactiva. Contacte al administrador jefe.']);
        }

        // CU 27.1 Excepción 2 / CU 33.1 Excepción 2: si el bloqueo sigue activo se informa
        // el tiempo restante y NO se reinicia el periodo de 60 minutos.
        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta > Carbon::now()) {
            $minutos = max(1, Carbon::now()->diffInMinutes($admin->bloqueado_hasta));

            return back()->withErrors([
                'correo' => "Cuenta bloqueada temporalmente por intentos fallidos. Vuelva a intentarlo en {$minutos} minuto(s).",
            ]);
        }

        // Si el bloqueo expiró, lo limpiamos
        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta <= Carbon::now()) {
            $admin->update(['bloqueado_hasta' => null, 'intentos_fallidos' => 0]);
        }

        if (Hash::check($request->password, $admin->password_hash)) {
            // Éxito
            $admin->update(['intentos_fallidos' => 0]);
            
            Auth::login($admin);
            
            // Registrar sesión
            Sesion::create([
                'id_admin' => $admin->id_admin,
                'token_hash' => hash('sha256', session()->getId()),
                'fecha_inicio' => Carbon::now(),
                'estado' => 'activa'
            ]);

            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        } else {
            // Fallo
            $admin->increment('intentos_fallidos');

            if ($admin->intentos_fallidos >= 5) {
                $admin->update(['bloqueado_hasta' => Carbon::now()->addMinutes(60)]);

                // CU 33.1 Excepciones 3 y 4: si el correo institucional no está disponible
                // o el servicio no responde, el bloqueo se aplica igual; solo se omite la
                // notificación y se registra el incidente.
                try {
                    Mail::to($admin->correo)->send(new CuentaBloqueadaMail($admin));
                } catch (\Throwable $e) {
                    Log::warning('No se pudo notificar el bloqueo de cuenta.', [
                        'id_admin' => $admin->id_admin,
                        'motivo' => $e->getMessage(),
                    ]);
                }

                return back()->withErrors(['correo' => 'Cuenta bloqueada por 60 minutos debido a múltiples intentos fallidos.']);
            }

            return back()->withErrors(['correo' => 'Credenciales inválidas.']);
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            // Cerrar sesión en BD
            Sesion::where('id_admin', Auth::id())
                  ->where('estado', 'activa')
                  ->update(['estado' => 'cerrada']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}