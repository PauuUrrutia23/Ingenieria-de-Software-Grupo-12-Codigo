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

class AuthController extends Controller
{
    private const REGLAS_PASSWORD = ['required', 'string', 'min:8', 'confirmed',
        'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'];

    public function __construct(
        private DBRouterController $db,
        private NotificationService $notificaciones,
    ) {
    }

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

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta > Carbon::now()) {
            $minutos = max(1, Carbon::now()->diffInMinutes($admin->bloqueado_hasta));

            return back()->withErrors([
                'correo' => "Cuenta bloqueada temporalmente por intentos fallidos. Vuelva a intentarlo en {$minutos} minuto(s).",
            ]);
        }

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta <= Carbon::now()) {
            $this->db->update($admin, ['bloqueado_hasta' => null, 'intentos_fallidos' => 0]);
        }

        if (Hash::check($request->password, $admin->password_hash)) {
            $this->db->update($admin, ['intentos_fallidos' => 0]);

            Auth::login($admin);

            $this->db->create(Sesion::class, [
                'id_admin' => $admin->id_admin,
                'token_hash' => hash('sha256', session()->getId()),
                'fecha_inicio' => Carbon::now(),
                'estado' => 'activa',
            ]);

            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        $admin->increment('intentos_fallidos');

        if ($admin->intentos_fallidos >= 5) {
            $this->db->update($admin, ['bloqueado_hasta' => Carbon::now()->addMinutes(60)]);

            $this->notificaciones->notificarBloqueoCuenta($admin);

            return back()->withErrors(['correo' => 'Cuenta bloqueada por 60 minutos debido a múltiples intentos fallidos.']);
        }

        return back()->withErrors(['correo' => 'Credenciales inválidas.']);
    }

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

    public function sendResetLink(Request $request)
    {
        $request->validate(['correo' => ['required', 'email']]);

        $admin = $this->db->query(Administrador::class)->where('correo', $request->correo)->first();

        if ($admin) {
            $token = Str::random(64);

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

            try {
                $this->notificaciones->notificarRecuperacionPassword($admin, $token);
            } catch (\Throwable $e) {
                report($e);

                return back()->with('success', 'Su solicitud fue registrada. El envío del correo podría demorar unos minutos.');
            }
        }

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
