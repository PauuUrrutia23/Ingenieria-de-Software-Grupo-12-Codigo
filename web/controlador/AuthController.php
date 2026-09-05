<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\RecuperacionPassword;
use App\Models\Sesion;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AuthController («Control») — Diagrama de Componentes: "Registro · Login · Sesión".
 *
 * Autenticación del Personal de Administración (RF27, RF32, RF33) y todo el ciclo
 * de contraseña: cambio estando autenticado (RF28) y recuperación por correo
 * (RF29-RF31).
 */
class AuthController extends Controller
{
    // DS-51: minimo 8 caracteres, 1 mayuscula, 1 minuscula, 1 numero y 1 caracter especial.
    private const REGLAS_PASSWORD = ['required', 'string', 'min:8', 'confirmed',
        'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'];

    public function __construct(
        private DBRouterController $db,
        private NotificationService $notificaciones,
    ) {
    }

    // ------------------------------------------------------------------
    // RF27 / CU 27.1 - Autenticando Personal de Administración
    // ------------------------------------------------------------------

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

        $admin = $this->db->query(Administrador::class)->where('correo', $request->correo)->first();

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
            $this->db->update($admin, ['bloqueado_hasta' => null, 'intentos_fallidos' => 0]);
        }

        if (Hash::check($request->password, $admin->password_hash)) {
            // Éxito
            $this->db->update($admin, ['intentos_fallidos' => 0]);

            Auth::login($admin);

            // Registrar sesión
            $this->db->create(Sesion::class, [
                'id_admin' => $admin->id_admin,
                'token_hash' => hash('sha256', session()->getId()),
                'fecha_inicio' => Carbon::now(),
                'estado' => 'activa',
            ]);

            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        // Fallo — CU 27.2
        $admin->increment('intentos_fallidos');

        if ($admin->intentos_fallidos >= 5) {
            $this->db->update($admin, ['bloqueado_hasta' => Carbon::now()->addMinutes(60)]);

            // CU 33.1 Excepciones 3 y 4: el bloqueo se aplica igual aunque el correo falle.
            $this->notificaciones->notificarBloqueoCuenta($admin);

            return back()->withErrors(['correo' => 'Cuenta bloqueada por 60 minutos debido a múltiples intentos fallidos.']);
        }

        return back()->withErrors(['correo' => 'Credenciales inválidas.']);
    }

    // ------------------------------------------------------------------
    // RF32 / CU 32.1 - Cerrando Sesión
    // ------------------------------------------------------------------

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $this->db->query(Sesion::class)
                ->where('id_admin', Auth::id())
                ->where('estado', 'activa')
                ->update(['estado' => 'cerrada']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // ------------------------------------------------------------------
    // RF28 / CU 28.1-28.2 - Cambiando contraseña estando autenticado
    // ------------------------------------------------------------------

    public function passwordEdit()
    {
        return view('admin.password.edit');
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'password_actual' => ['required'],
            'password' => self::REGLAS_PASSWORD,
        ], [], [
            'password' => 'la nueva contraseña',
        ]);

        $admin = Auth::user();

        if (!Hash::check($request->password_actual, $admin->password_hash)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        $this->db->update($admin, ['password_hash' => Hash::make($request->password)]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    // ------------------------------------------------------------------
    // RF29-RF31 / CU 29.1-31.2 - Recuperación de contraseña por correo
    // ------------------------------------------------------------------

    public function sendResetLink(Request $request)
    {
        $request->validate(['correo' => ['required', 'email']]);

        $admin = $this->db->query(Administrador::class)->where('correo', $request->correo)->first();

        if ($admin) {
            $token = Str::random(64);

            // CU 30.1 Excepción 3: la BD no permite generar/almacenar el Token de Sesión.
            try {
                $this->db->create(RecuperacionPassword::class, [
                    'id_admin' => $admin->id_admin,
                    'token_hash' => hash('sha256', $token),
                    'expira_en' => Carbon::now()->addMinutes(60),
                    'created_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors([
                    'correo' => 'No se pudo generar el enlace de recuperación. Por favor, intente nuevamente.',
                ]);
            }

            // CU 30.1 Excepción 4: el servicio de correo institucional no responde.
            try {
                $this->notificaciones->notificarRecuperacionPassword($admin, $token);
            } catch (\Throwable $e) {
                report($e);

                return back()->with('success', 'Su solicitud fue registrada. El envío del correo podría demorar unos minutos.');
            }
        }

        // Mensaje generico a proposito: no revela si el correo esta registrado (mismo
        // criterio de seguridad que CU 27.2).
        return back()->with('success', 'Si el correo ingresado corresponde a una cuenta, le enviamos un enlace de recuperación.');
    }

    public function showResetForm(string $token)
    {
        $recuperacion = $this->buscarTokenValido($token);

        if (!$recuperacion) {
            abort(404, 'El enlace de recuperación no es válido o ya expiró.');
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'password' => self::REGLAS_PASSWORD,
        ], [], [
            'password' => 'la nueva contraseña',
        ]);

        $recuperacion = $this->buscarTokenValido($request->token);

        if (!$recuperacion) {
            return back()->withErrors(['token' => 'El enlace de recuperación no es válido o ya expiró. Solicite uno nuevo.']);
        }

        $this->db->update($recuperacion->administrador, ['password_hash' => Hash::make($request->password)]);
        $this->db->update($recuperacion, ['usado_en' => Carbon::now()]);

        return redirect('/login')->with('success', 'Contraseña restablecida correctamente. Ya puede iniciar sesión.');
    }

    private function buscarTokenValido(string $token): ?RecuperacionPassword
    {
        return $this->db->query(RecuperacionPassword::class)
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('usado_en')
            ->where('expira_en', '>', Carbon::now())
            ->first();
    }
}
