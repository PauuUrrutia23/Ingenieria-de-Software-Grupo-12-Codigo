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
use Illuminate\Support\Facades\DB;
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

        try {
            $admin = $this->db->query(Administrador::class)->where('correo', $request->correo)->first();
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->only('correo'))->withErrors(['correo' => 'El acceso no está disponible temporalmente.']);
        }

        if (!$admin) {
            return back()->withInput($request->only('correo'))->withErrors(['correo' => 'Credenciales inválidas.']);
        }

        if (!$admin->activo) {
            return back()->withInput($request->only('correo'))->withErrors(['correo' => 'Cuenta inactiva. Contacte al administrador jefe.']);
        }

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta > Carbon::now()) {
            $minutos = max(1, Carbon::now()->diffInMinutes($admin->bloqueado_hasta));

            return back()->withInput($request->only('correo'))->withErrors([
                'correo' => "Cuenta bloqueada temporalmente por intentos fallidos. Vuelva a intentarlo en {$minutos} minuto(s).",
            ]);
        }

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta <= Carbon::now()) {
            try {
                $this->db->update($admin, ['bloqueado_hasta' => null, 'intentos_fallidos' => 0]);
            } catch (\Throwable $e) {
                report($e);
                return back()->withInput($request->only('correo'))->withErrors(['correo' => 'El acceso no está disponible temporalmente.']);
            }
        }

        if (Hash::check($request->password, $admin->password_hash)) {
            try {
                $this->db->update($admin, ['intentos_fallidos' => 0]);
            } catch (\Throwable $e) {
                report($e);
                return back()->withInput($request->only('correo'))->withErrors(['correo' => 'El acceso no está disponible temporalmente.']);
            }

            Auth::login($admin);

            // RF27: la sesión Laravel se regenera primero; la autoridad es la fila
            // "sesiones", ligada a un token aleatorio guardado en datos de sesión
            // (que sí persisten entre peticiones), no al id de sesión volátil.
            $request->session()->regenerate();

            $token = Str::random(48);
            $request->session()->put('sesion_ingecon', $token);

            $this->db->create(Sesion::class, [
                'id_admin' => $admin->id_admin,
                'token_hash' => hash('sha256', $token),
                'fecha_inicio' => Carbon::now(),
                'estado' => 'activa',
            ]);

            return redirect()->intended('/admin/dashboard');
        }

        try {
            $bloqueado = DB::transaction(function () use ($admin) {
                $actual = Administrador::query()->whereKey($admin->id_admin)->lockForUpdate()->firstOrFail();
                $intentos = $actual->intentos_fallidos + 1;
                $cambios = ['intentos_fallidos' => $intentos];
                if ($intentos >= 5) $cambios['bloqueado_hasta'] = Carbon::now()->addMinutes(60);
                $this->db->update($actual, $cambios);
                return $intentos >= 5;
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput($request->only('correo'))->withErrors(['correo' => 'El acceso no está disponible temporalmente.']);
        }

        if ($bloqueado) {
            $this->notificaciones->notificarBloqueoCuenta($admin);

            return back()->withInput($request->only('correo'))->withErrors(['correo' => 'Cuenta bloqueada por 60 minutos debido a múltiples intentos fallidos.']);
        }

        return back()->withInput($request->only('correo'))->withErrors(['correo' => 'Credenciales inválidas.']);
    }

    public function logout(Request $request)
    {
        // CU32.1: cerrar únicamente la sesión actual, no todas las del administrador.
        $token = $request->session()->get('sesion_ingecon');

        if (Auth::check() && $token !== null) {
            try {
                $this->db->query(Sesion::class)
                    ->where('id_admin', Auth::id())
                    ->where('token_hash', hash('sha256', $token))
                    ->update(['estado' => 'cerrada']);
            } catch (\Throwable $e) {
                // No exponer SQL: se invalida el acceso local por seguridad y se registra.
                report($e);
            }
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

        try {
            DB::transaction(fn () => $this->db->update($admin, ['password_hash' => Hash::make($request->password)]));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['password' => 'No se pudo actualizar la contraseña. Intente nuevamente.']);
        }

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

        try {
            $actualizada = DB::transaction(function () use ($recuperacion, $request) {
                $token = RecuperacionPassword::query()->whereKey($recuperacion->id_recuperacion)
                    ->whereNull('usado_en')->where('expira_en', '>', Carbon::now())
                    ->lockForUpdate()->first();
                if (!$token || !$token->administrador) return false;
                $this->db->update($token->administrador, ['password_hash' => Hash::make($request->password)]);
                $this->db->update($token, ['usado_en' => Carbon::now()]);
                return true;
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['password' => 'No se pudo restablecer la contraseña. Intente nuevamente.']);
        }
        if (!$actualizada) {
            return back()->withErrors(['token' => 'El enlace de recuperación no es válido o ya expiró. Solicite uno nuevo.']);
        }

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
