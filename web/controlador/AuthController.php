<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarEmailBloqueoJob;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const SESSION_MINUTES = 120;
    private const COOKIE_NAME = 'ingecon_auth';
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_MINUTOS = 60;

    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Procesa el login del modal y retorna JSON para fetch().
     *
     * La cookie almacena "id_sesion|token": el middleware busca la sesión por
     * ID y verifica solo ese token_hash, sin iterar todas las sesiones activas.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'correo'   => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        $admin = $this->db->buscarAdminPorCorreo($validated['correo']);

        // No revelar si el correo existe.
        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'campo'   => 'correo',
            ], 401);
        }

        if (! $admin->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador del sistema.',
                'campo'   => 'correo',
            ], 403);
        }

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta->isFuture()) {
            $minutosRestantes = (int) now()->diffInMinutes($admin->bloqueado_hasta, true);

            return response()->json([
                'success' => false,
                'message' => "Tu cuenta está bloqueada temporalmente. Podrás intentarlo nuevamente en {$minutosRestantes} minuto(s).",
                'campo'   => 'correo',
            ], 423);
        }

        $passwordCorrecto = Hash::check($validated['password'], $admin->password_hash);

        if (! $passwordCorrecto) {
            $admin->intentos_fallidos += 1;

            if ($admin->intentos_fallidos >= self::MAX_INTENTOS) {
                $admin->bloqueado_hasta   = now()->addMinutes(self::BLOQUEO_MINUTOS);
                $admin->intentos_fallidos = 0;

                try {
                    $this->db->guardarAdmin($admin);
                } catch (QueryException $e) {
                    Log::error('BD: No se pudo registrar el bloqueo de cuenta', [
                        'error'       => $e->getMessage(),
                        'id_admin'    => $admin->id_admin,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Error interno al procesar la autenticación. Intenta nuevamente.',
                    ], 500);
                }

                EnviarEmailBloqueoJob::dispatch($admin->id_admin, now());

                Log::warning('Cuenta bloqueada por intentos fallidos', [
                    'id_admin'        => $admin->id_admin,
                    'correo'          => $admin->correo,
                    'bloqueado_hasta' => $admin->bloqueado_hasta,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta ha sido bloqueada por 60 minutos debido a múltiples intentos fallidos. Se ha enviado un aviso a tu correo electrónico.',
                    'campo'   => 'password',
                ], 423);
            }

            try {
                $this->db->guardarAdmin($admin);
            } catch (QueryException $e) {
                Log::error('BD: No se pudo actualizar el contador de intentos', [
                    'error'    => $e->getMessage(),
                    'id_admin' => $admin->id_admin,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno al procesar la autenticación. Intenta nuevamente.',
                ], 500);
            }

            $restantes = self::MAX_INTENTOS - $admin->intentos_fallidos;

            return response()->json([
                'success' => false,
                'message' => "Credenciales incorrectas. Te quedan {$restantes} intento(s) antes del bloqueo.",
                'campo'   => 'password',
            ], 401);
        }

        // Contraseña correcta: resetear contadores y abrir sesión.
        $admin->intentos_fallidos = 0;
        $admin->bloqueado_hasta   = null;

        try {
            $this->db->guardarAdmin($admin);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo resetear los contadores de seguridad', [
                'error'    => $e->getMessage(),
                'id_admin' => $admin->id_admin,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno al procesar la autenticación. Intenta nuevamente.',
            ], 500);
        }

        $token = Str::random(64);

        // Persistir solo el hash del token, nunca el token en claro.
        try {
            $sesion = $this->db->crearSesion([
                'token_hash'   => Hash::make($token),
                'fecha_inicio' => now(),
                'estado'       => 'activa',
                'id_admin'     => $admin->id_admin,
            ]);
        } catch (QueryException $e) {
            Log::error('BD: No se pudo crear la sesión', [
                'error'    => $e->getMessage(),
                'id_admin' => $admin->id_admin,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar sesión. La base de datos no está disponible temporalmente.',
            ], 500);
        }

        $valorCookie = $sesion->id_sesion . '|' . $token;

        $cookie = cookie(
            name:     self::COOKIE_NAME,
            value:    $valorCookie,
            minutes:  self::SESSION_MINUTES,
            path:     '/',
            domain:   null,
            secure:   app()->isProduction(),
            httpOnly: true,
            sameSite: 'Strict'
        );

        return response()->json([
            'success'  => true,
            'redirect' => route('admin.dashboard'),
        ])->withCookie($cookie);
    }

    /**
     * Invalida la sesión activa y elimina la cookie.
     */
    public function logout(Request $request): RedirectResponse
    {
        $valorCookie = $request->cookie(self::COOKIE_NAME);

        if ($valorCookie) {
            $partes = explode('|', $valorCookie, 2);

            if (count($partes) === 2) {
                [$idSesion, $token] = $partes;

                /** @var \App\Models\Administrador $admin */
                $admin = $request->attributes->get('admin');

                if ($admin) {
                    $sesionActiva = $this->db->buscarSesionActivaDeAdmin((int) $idSesion, $admin->id_admin);

                    if ($sesionActiva && Hash::check($token, $sesionActiva->token_hash)) {
                        $sesionActiva->estado = 'cerrada';

                        try {
                            $this->db->guardarSesion($sesionActiva);
                        } catch (QueryException $e) {
                            Log::error('BD: No se pudo cerrar la sesión en BD', [
                                'error'    => $e->getMessage(),
                                'id_sesion'=> $sesionActiva->id_sesion,
                            ]);
                        }
                    }
                }
            }
        }

        $cookieExpirada = cookie()->forget(self::COOKIE_NAME);

        return redirect('/')
            ->withCookie($cookieExpirada)
            ->with('info', 'Sesión cerrada correctamente.');
    }
}
