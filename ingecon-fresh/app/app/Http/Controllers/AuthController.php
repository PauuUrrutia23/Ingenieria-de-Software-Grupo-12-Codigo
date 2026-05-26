<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarEmailBloqueoJob;
use App\Models\Administrador;
use App\Models\Sesion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Duración de la sesión en minutos.
     */
    private const SESSION_MINUTES = 120;

    /**
     * Nombre de la cookie de sesión.
     */
    private const COOKIE_NAME = 'ingecon_session';

    /**
     * Máximo de intentos antes del bloqueo.
     */
    private const MAX_INTENTOS = 5;

    /**
     * Duración del bloqueo en minutos.
     */
    private const BLOQUEO_MINUTOS = 60;

    // =========================================================================
    // CU 5.1 — Autenticando Personal de Administración (RF28)
    // =========================================================================

    /**
     * Procesa el formulario de login enviado desde el modal Alpine.js.
     * Siempre retorna JSON para ser consumido por fetch() en el frontend.
     *
     * Cookie de sesión: almacena "id_sesion|token" en lugar de solo el token.
     * Esto permite al middleware buscar la sesión por ID y verificar solo ese
     * registro con Hash::check, evitando cargar todas las sesiones activas.
     *
     * @param Request $request  Campos: correo (string), password (string)
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // ------------------------------------------------------------------
        // a) Validar request — errores retornan JSON 422
        // ------------------------------------------------------------------
        $validated = $request->validate([
            'correo'   => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        // ------------------------------------------------------------------
        // b) Buscar Administrador por correo
        // ------------------------------------------------------------------
        $admin = Administrador::where('correo', $validated['correo'])->first();

        // ------------------------------------------------------------------
        // c) Administrador no existe → error genérico (no revelar existencia)
        // ------------------------------------------------------------------
        if (! $admin) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'campo'   => 'correo',
            ], 401);
        }

        // ------------------------------------------------------------------
        // d) Cuenta desactivada (activo = false)
        // ------------------------------------------------------------------
        if (! $admin->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador del sistema.',
                'campo'   => 'correo',
            ], 403);
        }

        // ------------------------------------------------------------------
        // e) Verificar bloqueo temporal — bloqueado_hasta > now()
        // ------------------------------------------------------------------
        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta->isFuture()) {
            $minutosRestantes = (int) now()->diffInMinutes($admin->bloqueado_hasta, true);

            return response()->json([
                'success' => false,
                'message' => "Tu cuenta está bloqueada temporalmente. Podrás intentarlo nuevamente en {$minutosRestantes} minuto(s).",
                'campo'   => 'correo',
            ], 423);
        }

        // ------------------------------------------------------------------
        // f) Verificar contraseña con Argon2id
        // ------------------------------------------------------------------
        $passwordCorrecto = Hash::check($validated['password'], $admin->password_hash);

        // ------------------------------------------------------------------
        // g) Contraseña INCORRECTA → incrementar intentos y evaluar bloqueo
        // ------------------------------------------------------------------
        if (! $passwordCorrecto) {
            $admin->intentos_fallidos += 1;

            if ($admin->intentos_fallidos >= self::MAX_INTENTOS) {
                // CU 5.7 — Bloquear cuenta por 60 minutos
                $admin->bloqueado_hasta   = now()->addMinutes(self::BLOQUEO_MINUTOS);
                $admin->intentos_fallidos = 0;
                $admin->save();

                // Disparar job asíncrono para enviar email de bloqueo
                EnviarEmailBloqueoJob::dispatch($admin->id_admin, now()->toImmutable());

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

            $admin->save();

            $restantes = self::MAX_INTENTOS - $admin->intentos_fallidos;

            return response()->json([
                'success' => false,
                'message' => "Credenciales incorrectas. Te quedan {$restantes} intento(s) antes del bloqueo.",
                'campo'   => 'password',
            ], 401);
        }

        // ------------------------------------------------------------------
        // h) Contraseña CORRECTA → crear sesión
        // ------------------------------------------------------------------

        // Resetear contadores de seguridad
        $admin->intentos_fallidos = 0;
        $admin->bloqueado_hasta   = null;
        $admin->save();

        // Generar token aleatorio de 64 caracteres
        $token = Str::random(64);

        // Persistir sesión con hash del token (nunca guardar el token en claro)
        $sesion = Sesion::create([
            'token_hash'   => Hash::make($token),
            'fecha_inicio' => now(),
            'estado'       => 'activa',
            'id_admin'     => $admin->id_admin,
        ]);

        // Construir valor de cookie: "id_sesion|token"
        // El middleware usará el id_sesion para buscar el registro directamente
        // y verificar solo ese token_hash, sin iterar todas las sesiones activas.
        $valorCookie = $sesion->id_sesion . '|' . $token;

        // Construir cookie httpOnly segura
        $cookie = cookie(
            name:     self::COOKIE_NAME,
            value:    $valorCookie,
            minutes:  self::SESSION_MINUTES,
            path:     '/',
            domain:   null,
            secure:   app()->isProduction(),  // HTTPS solo en producción
            httpOnly: true,
            sameSite: 'Strict'
        );

        return response()->json([
            'success'  => true,
            'redirect' => route('admin.dashboard'),
        ])->withCookie($cookie);
    }

    // =========================================================================
    // CU 5.6 — Cerrando Sesión (RF33)
    // =========================================================================

    /**
     * Invalida la sesión activa y elimina la cookie.
     * El middleware admin.auth garantiza que existe una sesión válida al llegar aquí.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        // ------------------------------------------------------------------
        // a) Leer cookie y parsear "id_sesion|token"
        // ------------------------------------------------------------------
        $valorCookie = $request->cookie(self::COOKIE_NAME);

        if ($valorCookie) {
            $partes = explode('|', $valorCookie, 2);

            if (count($partes) === 2) {
                [$idSesion, $token] = $partes;

                // --------------------------------------------------------------
                // b) Recuperar admin inyectado por el middleware AdminAuth
                // --------------------------------------------------------------
                /** @var Administrador $admin */
                $admin = $request->attributes->get('admin');

                if ($admin) {
                    // ----------------------------------------------------------
                    // c) Buscar la sesión directamente por ID — sin iterar
                    // ----------------------------------------------------------
                    $sesionActiva = Sesion::where('id_sesion', (int) $idSesion)
                        ->where('id_admin', $admin->id_admin)
                        ->where('estado', 'activa')
                        ->first();

                    if ($sesionActiva && Hash::check($token, $sesionActiva->token_hash)) {
                        // ------------------------------------------------------
                        // c) Invalidar la sesión
                        // ------------------------------------------------------
                        $sesionActiva->estado = 'cerrada';
                        $sesionActiva->save();
                    }
                }
            }
        }

        // ------------------------------------------------------------------
        // d) Eliminar cookie enviando una caducada
        // ------------------------------------------------------------------
        $cookieExpirada = cookie()->forget(self::COOKIE_NAME);

        // ------------------------------------------------------------------
        // e) Redirigir al inicio
        // ------------------------------------------------------------------
        return redirect('/')
            ->withCookie($cookieExpirada)
            ->with('info', 'Sesión cerrada correctamente.');
    }
}
