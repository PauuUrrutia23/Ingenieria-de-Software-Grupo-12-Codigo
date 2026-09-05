<?php
namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\RecuperacionPassword;
use App\Mail\PasswordResetLinkMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordController extends Controller
{
    // DS-51: minimo 8 caracteres, 1 mayuscula, 1 minuscula, 1 numero y 1 caracter especial.
    private const REGLAS_PASSWORD = ['required', 'string', 'min:8', 'confirmed',
        'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/'];

    /**
     * CU 28.1 - Solicitando el cambio de contraseña.
     */
    public function edit()
    {
        return view('admin.password.edit');
    }

    /**
     * CU 28.2 - Confirmando el cambio de contraseña.
     */
    public function update(Request $request)
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

        $admin->update(['password_hash' => Hash::make($request->password)]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    /**
     * CU 30.1 - Enviando enlace seguro de recuperación.
     * (CU 29.1, iniciar el formulario, se resuelve con un modal en el login, sin backend propio)
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['correo' => ['required', 'email']]);

        $admin = Administrador::where('correo', $request->correo)->first();

        if ($admin) {
            $token = Str::random(64);

            // CU 30.1 Excepción 3: la BD no permite generar/almacenar el Token de Sesión.
            try {
                RecuperacionPassword::create([
                    'id_admin' => $admin->id_admin,
                    'token_hash' => hash('sha256', $token),
                    'expira_en' => Carbon::now()->addMinutes(60),
                    'created_at' => Carbon::now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('No se pudo generar el token de recuperación.', ['motivo' => $e->getMessage()]);

                return back()->withErrors([
                    'correo' => 'No se pudo generar el enlace de recuperación. Por favor, intente nuevamente.',
                ]);
            }

            // CU 30.1 Excepción 4: el servicio de correo institucional no responde. Se
            // registra el incidente y se informa que el envío podría demorar.
            try {
                Mail::to($admin->correo)->send(new PasswordResetLinkMail($admin, $token));
            } catch (\Throwable $e) {
                Log::warning('El servicio de correo no respondió al enviar el enlace de recuperación.', [
                    'id_admin' => $admin->id_admin,
                    'motivo' => $e->getMessage(),
                ]);

                return back()->with('success', 'Su solicitud fue registrada. El envío del correo podría demorar unos minutos.');
            }
        }

        // Mensaje generico a proposito: no revela si el correo esta registrado (mismo
        // criterio de seguridad que CU 27.2).
        return back()->with('success', 'Si el correo ingresado corresponde a una cuenta, le enviamos un enlace de recuperación.');
    }

    /**
     * CU 31.1 - Validando el Enlace seguro de recuperación.
     */
    public function showResetForm(string $token)
    {
        $recuperacion = $this->buscarTokenValido($token);

        if (!$recuperacion) {
            abort(404, 'El enlace de recuperación no es válido o ya expiró.');
        }

        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * CU 31.2 - Restableciendo la contraseña desde el Formulario.
     */
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

        $recuperacion->administrador->update(['password_hash' => Hash::make($request->password)]);
        $recuperacion->update(['usado_en' => Carbon::now()]);

        return redirect('/login')->with('success', 'Contraseña restablecida correctamente. Ya puede iniciar sesión.');
    }

    private function buscarTokenValido(string $token): ?RecuperacionPassword
    {
        return RecuperacionPassword::where('token_hash', hash('sha256', $token))
            ->whereNull('usado_en')
            ->where('expira_en', '>', Carbon::now())
            ->first();
    }
}
