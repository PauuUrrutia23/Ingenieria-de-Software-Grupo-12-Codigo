# 03 — Sistema de Autenticación Personalizado
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Especificación completa del sistema de autenticación sin Breeze ni Fortify, implementado desde cero sobre Laravel 11 + PostgreSQL 16.  
> **Requerimientos cubiertos:** RF28, RF33, RF34 / CU 5.1, CU 5.6, CU 5.7.  
> **Convención:** Todos los modelos siguen `02_modelos_eloquent.md`. Las migraciones ya existen según `01_setup_laravel_migraciones.md`.
> **Acceso a datos:** Toda interacción con la BD se delega en `DBRouterController` (`02b_dbrouter_controller.md`), inyectado por constructor en `AuthController` y `AdminAuth`, y resuelto en `handle()` en `EnviarEmailBloqueoJob`. No hay llamadas directas a Eloquent en este archivo.

---

## Estructura de Archivos a Crear

```
app/
├── Http/
│   ├── Controllers/
│   │   └── AuthController.php
│   └── Middleware/
│       └── AdminAuth.php
├── Mail/
│   └── CuentaBloqueadaMail.php
├── Jobs/
│   └── EnviarEmailBloqueoJob.php
└── Models/
    ├── Administrador.php   (ya definido en 02_modelos_eloquent.md)
    └── Sesion.php          (ya definido en 02_modelos_eloquent.md)

resources/views/
└── auth/
    ├── login-modal.blade.php
    └── emails/
        └── cuenta-bloqueada.blade.php

routes/
└── web.php                 (sección a agregar)
```

---

## 1. Rutas — `routes/web.php`

Agregar las siguientes rutas al archivo existente. Las rutas del panel de administración se protegen con el middleware `admin.auth`.

```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------------------
// Rutas públicas de autenticación
// -------------------------------------------------------------------------

Route::post('/login', [AuthController::class, 'login'])
    ->name('auth.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('admin.auth');

// -------------------------------------------------------------------------
// Rutas protegidas del panel de administración
// Todas las rutas bajo /admin requieren sesión activa válida.
// -------------------------------------------------------------------------

Route::prefix('admin')
    ->middleware('admin.auth')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Registrar aquí el resto de rutas del panel de gestión
    });
```

### Registro del Middleware en `bootstrap/app.php`

En Laravel 11 los middlewares se registran en `bootstrap/app.php`, no en `Kernel.php`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Alias para usar como middleware de ruta
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 2. `AuthController` — Autenticación Completa

**Archivo:** `app/Http/Controllers/AuthController.php`

```php
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

    /**
     * Mediador de base de datos. Toda lectura/escritura pasa por aquí
     * (ver 02b_dbrouter_controller.md). Resuelto por el contenedor de Laravel.
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

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
        $admin = $this->db->buscarAdminPorCorreo($validated['correo']);

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
                $this->db->guardarAdmin($admin);

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

            $this->db->guardarAdmin($admin);

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
        $this->db->guardarAdmin($admin);

        // Generar token aleatorio de 64 caracteres
        $token = Str::random(64);

        // Persistir sesión con hash del token (nunca guardar el token en claro)
        $sesion = $this->db->crearSesion([
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
                    $sesionActiva = $this->db->buscarSesionActivaDeAdmin((int) $idSesion, $admin->id_admin);

                    if ($sesionActiva && Hash::check($token, $sesionActiva->token_hash)) {
                        // ------------------------------------------------------
                        // c) Invalidar la sesión
                        // ------------------------------------------------------
                        $sesionActiva->estado = 'cerrada';
                        $this->db->guardarSesion($sesionActiva);
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
```

---

## 3. Middleware — `AdminAuth`

**Archivo:** `app/Http/Middleware/AdminAuth.php`

```php
<?php

namespace App\Http\Middleware;

use App\Models\Administrador;
use App\Models\Sesion;
use App\Http\Controllers\DBRouterController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Nombre de la cookie de sesión (debe coincidir con AuthController::COOKIE_NAME).
     */
    private const COOKIE_NAME = 'ingecon_session';

    /**
     * Mediador de base de datos (ver 02b_dbrouter_controller.md).
     * Laravel resuelve el middleware a través del contenedor, por lo que
     * la inyección por constructor funciona sin registro adicional.
     */
    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Verifica que la request incluya una cookie de sesión válida y activa.
     * Si es válida, inyecta el modelo Administrador en $request->attributes.
     *
     * @param Request  $request
     * @param Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ------------------------------------------------------------------
        // a) Leer cookie y parsear "id_sesion|token"
        // ------------------------------------------------------------------
        $valorCookie = $request->cookie(self::COOKIE_NAME);

        if (! $valorCookie) {
            return $this->rechazar($request, 'Debes iniciar sesión para acceder.');
        }

        $partes = explode('|', $valorCookie, 2);

        if (count($partes) !== 2) {
            return $this->rechazar($request, 'Sesión inválida. Por favor inicia sesión nuevamente.');
        }

        [$idSesion, $token] = $partes;

        // ------------------------------------------------------------------
        // b) Buscar la sesión directamente por ID — O(1), sin iterar
        //    Solo se verifica el token_hash de ese registro específico.
        // ------------------------------------------------------------------
        $sesion = $this->db->buscarSesionActivaPorId((int) $idSesion);

        // ------------------------------------------------------------------
        // c) Verificar que el token en claro coincide con el hash almacenado
        // ------------------------------------------------------------------
        if (! $sesion || ! Hash::check($token, $sesion->token_hash)) {
            return $this->rechazar($request, 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        }

        // ------------------------------------------------------------------
        // d) Verificar que el administrador existe, está activo y no bloqueado
        // ------------------------------------------------------------------
        $admin = $this->db->buscarAdminPorId($sesion->id_admin);

        if (! $admin || ! $admin->activo) {
            return $this->rechazar($request, 'Tu cuenta no tiene acceso al panel de administración.');
        }

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta->isFuture()) {
            return $this->rechazar($request, 'Tu cuenta está bloqueada temporalmente.');
        }

        // ------------------------------------------------------------------
        // e) Sesión válida → inyectar $admin y $sesion en request y continuar
        // ------------------------------------------------------------------
        $request->attributes->set('admin', $admin);
        $request->attributes->set('sesion', $sesion);

        return $next($request);
    }

    /**
     * Responde un rechazo según el tipo de request:
     * - JSON (fetch/XHR): retorna 401 JSON
     * - HTML: redirige a '/' con flash de error y elimina cookie
     *
     * @param Request $request
     * @param string  $mensaje
     * @return Response
     */
    private function rechazar(Request $request, string $mensaje): Response
    {
        $cookieExpirada = cookie()->forget(self::COOKIE_NAME);

        if ($request->expectsJson()) {
            return response()
                ->json(['success' => false, 'message' => $mensaje], 401)
                ->withCookie($cookieExpirada);
        }

        return redirect('/')
            ->withCookie($cookieExpirada)
            ->with('error', $mensaje);
    }
}
```

---

## 4. Job — `EnviarEmailBloqueoJob`

**Archivo:** `app/Jobs/EnviarEmailBloqueoJob.php`

El job recibe el `id_admin` (no el modelo) para ser seguro frente a la serialización de Eloquent en colas.

```php
<?php

namespace App\Jobs;

use App\Mail\CuentaBloqueadaMail;
use App\Models\Administrador;
use App\Http\Controllers\DBRouterController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarEmailBloqueoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de reintentos en caso de fallo de envío.
     */
    public int $tries = 3;

    /**
     * Tiempo de espera entre reintentos (segundos).
     */
    public int $backoff = 30;

    /**
     * @param int    $adminId    ID del administrador bloqueado
     * @param Carbon $momentoBloqueo  Instancia Carbon inmutable del momento del bloqueo
     */
    public function __construct(
        private readonly int    $adminId,
        private readonly Carbon $momentoBloqueo,
    ) {}

    /**
     * Ejecutar el job: cargar el admin y enviar el email de notificación.
     * El DBRouterController se resuelve desde el contenedor en handle()
     * (no por constructor, para no romper la serialización del job).
     */
    public function handle(DBRouterController $db): void
    {
        $admin = $db->buscarAdminPorId($this->adminId);

        if (! $admin) {
            Log::error('EnviarEmailBloqueoJob: Administrador no encontrado', [
                'id_admin' => $this->adminId,
            ]);
            return;
        }

        Mail::to($admin->correo)
            ->send(new CuentaBloqueadaMail($admin, $this->momentoBloqueo));

        Log::info('Email de bloqueo enviado', [
            'id_admin' => $admin->id_admin,
            'correo'   => $admin->correo,
        ]);
    }
}
```

---

## 5. Mailable — `CuentaBloqueadaMail`

**Archivo:** `app/Mail/CuentaBloqueadaMail.php`

```php
<?php

namespace App\Mail;

use App\Models\Administrador;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CuentaBloqueadaMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Administrador $admin          El administrador cuya cuenta fue bloqueada
     * @param Carbon        $momentoBloqueo Momento exacto en que se produjo el bloqueo
     */
    public function __construct(
        public readonly Administrador $admin,
        public readonly Carbon        $momentoBloqueo,
    ) {}

    /**
     * Asunto y remitente del email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Ingecon] Alerta de seguridad: cuenta bloqueada temporalmente',
        );
    }

    /**
     * Vista Blade que renderiza el cuerpo del email.
     * Pasa las variables públicas automáticamente a la vista.
     */
    public function content(): Content
    {
        return new Content(
            view: 'auth.emails.cuenta-bloqueada',
            with: [
                'correo'          => $this->admin->correo,
                'momentoBloqueo'  => $this->momentoBloqueo->format('d/m/Y H:i:s'),
                'desbloqueoHora'  => $this->momentoBloqueo->addMinutes(60)->format('H:i'),
                'desbloqueoFecha' => $this->momentoBloqueo->addMinutes(60)->format('d/m/Y'),
                'duracionMinutos' => 60,
            ],
        );
    }
}
```

---

## 6. Vista Email — `cuenta-bloqueada.blade.php`

**Archivo:** `resources/views/auth/emails/cuenta-bloqueada.blade.php`

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de seguridad — Ingecon</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f5;
            margin: 0;
            padding: 0;
            color: #18181b;
        }
        .container {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }
        .header {
            background-color: #1a1a2e;
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .header span {
            color: #f59e0b;
        }
        .alert-badge {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 20px;
            margin: 24px 32px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #92400e;
            font-weight: 600;
        }
        .body {
            padding: 24px 32px 32px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.6;
            color: #3f3f46;
            margin: 0 0 16px;
        }
        .info-box {
            background-color: #f4f4f5;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .info-box td {
            padding: 6px 0;
            color: #52525b;
        }
        .info-box td:first-child {
            font-weight: 600;
            color: #18181b;
            width: 160px;
        }
        .footer {
            background-color: #f4f4f5;
            padding: 16px 32px;
            text-align: center;
            font-size: 12px;
            color: #a1a1aa;
            border-top: 1px solid #e4e4e7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>INGE<span>CON</span> — Seguridad</h1>
        </div>

        <div class="alert-badge">
            ⚠ Alerta de seguridad: Tu cuenta ha sido bloqueada temporalmente
        </div>

        <div class="body">
            <p>Hola,</p>
            <p>
                Hemos detectado <strong>5 intentos fallidos de inicio de sesión</strong>
                consecutivos en tu cuenta de administrador (<strong>{{ $correo }}</strong>).
                Como medida de protección, el acceso ha sido bloqueado de forma temporal.
            </p>

            <div class="info-box">
                <table>
                    <tr>
                        <td>Momento del bloqueo:</td>
                        <td>{{ $momentoBloqueo }}</td>
                    </tr>
                    <tr>
                        <td>Duración del bloqueo:</td>
                        <td>{{ $duracionMinutos }} minutos</td>
                    </tr>
                    <tr>
                        <td>Acceso habilitado a:</td>
                        <td>{{ $desbloqueoHora }} hrs del {{ $desbloqueoFecha }}</td>
                    </tr>
                </table>
            </div>

            <p>
                Si fuiste tú quien realizó estos intentos, no se requiere ninguna acción.
                Tu cuenta se desbloqueará automáticamente al término del período indicado.
            </p>
            <p>
                Si <strong>no reconoces</strong> estos intentos de acceso, te recomendamos
                cambiar tu contraseña inmediatamente una vez que el bloqueo expire y contactar
                al administrador del sistema.
            </p>
        </div>

        <div class="footer">
            Este es un mensaje automático del sistema de seguridad de Ingecon.<br>
            Por favor no respondas a este correo.
        </div>
    </div>
</body>
</html>
```

---

## 7. Vista Blade — Modal de Login Alpine.js

**Archivo:** `resources/views/auth/login-modal.blade.php`

Este partial se incluye con `@include('auth.login-modal')` en el layout principal (`layouts/app.blade.php`). El modal se controla con Alpine.js y se abre escuchando el evento global `abrir-login`.

```blade
{{--
    Modal de Login — Ingecon
    Inclusión: @include('auth.login-modal')  en el layout principal
    Apertura:  $dispatch('abrir-login') desde cualquier componente Alpine
               o window.dispatchEvent(new CustomEvent('abrir-login')) desde JS puro
--}}

<div
    x-data="loginModal()"
    x-show="abierto"
    x-cloak
    @abrir-login.window="abrir()"
    @keydown.escape.window="cerrar()"
    class="fixed inset-0 z-50 flex items-center justify-center"
    aria-modal="true"
    role="dialog"
    aria-labelledby="modal-login-titulo"
>
    {{-- Overlay oscuro --}}
    <div
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        @click="cerrar()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Panel del modal --}}
    <div
        class="relative z-10 w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
    >
        {{-- Cabecera --}}
        <div class="bg-slate-900 px-8 py-6 flex items-center justify-between">
            <div>
                <h2
                    id="modal-login-titulo"
                    class="text-white text-xl font-bold tracking-wide"
                >
                    Panel de Gestión
                </h2>
                <p class="text-slate-400 text-sm mt-0.5">Ingresa tus credenciales para continuar</p>
            </div>
            <button
                @click="cerrar()"
                class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg"
                aria-label="Cerrar modal"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Formulario --}}
        <div class="px-8 py-7">

            {{-- Mensaje de error general (bloqueo, cuenta desactivada) --}}
            <div
                x-show="errorGeneral"
                x-cloak
                class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3 flex items-start gap-3"
                role="alert"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 mt-0.5 shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="text-red-700 text-sm" x-text="errorGeneral"></p>
            </div>

            {{-- Campo: Correo electrónico --}}
            <div class="mb-5">
                <label
                    for="login-correo"
                    class="block text-sm font-semibold text-slate-700 mb-1.5"
                >
                    Correo electrónico
                </label>
                <input
                    id="login-correo"
                    type="email"
                    x-model="form.correo"
                    @input="limpiarError('correo')"
                    :class="errores.correo ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-slate-300'"
                    class="w-full px-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400"
                    placeholder="admin@ingecon.cl"
                    autocomplete="email"
                    inputmode="email"
                    :disabled="cargando"
                >
                <p
                    x-show="errores.correo"
                    x-cloak
                    x-text="errores.correo"
                    class="mt-1.5 text-xs text-red-600"
                ></p>
            </div>

            {{-- Campo: Contraseña --}}
            <div class="mb-6">
                <label
                    for="login-password"
                    class="block text-sm font-semibold text-slate-700 mb-1.5"
                >
                    Contraseña
                </label>
                <div class="relative">
                    <input
                        id="login-password"
                        :type="mostrarPassword ? 'text' : 'password'"
                        x-model="form.password"
                        @input="limpiarError('password')"
                        @keydown.enter="enviar()"
                        :class="errores.password ? 'border-red-400 focus:ring-red-300' : 'border-slate-300 focus:ring-slate-300'"
                        class="w-full px-4 py-2.5 pr-11 rounded-lg border text-sm focus:outline-none focus:ring-2 transition-colors placeholder:text-slate-400"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        :disabled="cargando"
                    >
                    {{-- Toggle visibilidad contraseña --}}
                    <button
                        type="button"
                        @click="mostrarPassword = !mostrarPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                        :aria-label="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                        <svg x-show="!mostrarPassword" xmlns="http://www.w3.org/2000/svg"
                             class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <svg x-show="mostrarPassword" x-cloak xmlns="http://www.w3.org/2000/svg"
                             class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                <p
                    x-show="errores.password"
                    x-cloak
                    x-text="errores.password"
                    class="mt-1.5 text-xs text-red-600"
                ></p>
            </div>

            {{-- Botón de envío --}}
            <button
                type="button"
                @click="enviar()"
                :disabled="cargando"
                class="w-full bg-slate-900 hover:bg-slate-700 disabled:bg-slate-400 text-white font-semibold text-sm py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
                <svg
                    x-show="cargando"
                    x-cloak
                    class="animate-spin w-4 h-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="cargando ? 'Verificando...' : 'Ingresar'"></span>
            </button>

        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    function loginModal() {
        return {
            abierto: false,
            cargando: false,
            mostrarPassword: false,
            errorGeneral: '',
            form: {
                correo: '',
                password: '',
            },
            errores: {
                correo: '',
                password: '',
            },

            abrir() {
                this.resetear();
                this.abierto = true;
                // Enfocar el campo correo tras la animación de apertura
                this.$nextTick(() => {
                    document.getElementById('login-correo')?.focus();
                });
            },

            cerrar() {
                this.abierto = false;
                this.resetear();
            },

            resetear() {
                this.form = { correo: '', password: '' };
                this.errores = { correo: '', password: '' };
                this.errorGeneral = '';
                this.cargando = false;
                this.mostrarPassword = false;
            },

            limpiarError(campo) {
                this.errores[campo] = '';
                this.errorGeneral = '';
            },

            async enviar() {
                // Evitar doble envío
                if (this.cargando) return;

                // Validación client-side básica
                let valido = true;
                this.errores = { correo: '', password: '' };
                this.errorGeneral = '';

                if (! this.form.correo) {
                    this.errores.correo = 'El correo es obligatorio.';
                    valido = false;
                } else if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.correo)) {
                    this.errores.correo = 'Ingresa un correo electrónico válido.';
                    valido = false;
                }

                if (! this.form.password) {
                    this.errores.password = 'La contraseña es obligatoria.';
                    valido = false;
                }

                if (! valido) return;

                this.cargando = true;

                try {
                    const response = await fetch('/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            correo: this.form.correo,
                            password: this.form.password,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Login exitoso — redirigir al panel
                        window.location.href = data.redirect;
                        return; // No resetear cargando (la página cambia)
                    }

                    // Mostrar error según campo afectado
                    if (data.campo && this.errores.hasOwnProperty(data.campo)) {
                        this.errores[data.campo] = data.message;
                    } else {
                        this.errorGeneral = data.message ?? 'Error al iniciar sesión. Intenta nuevamente.';
                    }

                } catch (err) {
                    this.errorGeneral = 'Error de conexión. Verifica tu red e intenta nuevamente.';
                    console.error('Error en login:', err);
                } finally {
                    this.cargando = false;
                    // Limpiar contraseña siempre tras intento fallido
                    this.form.password = '';
                }
            },
        };
    }
</script>
@endpush
@endonce
```

---

## 8. Configuración `.env` para Email con Resend

Agregar o actualizar las siguientes variables en `.env`:

```ini
# -------------------------------------------------------
# Email — Resend vía SMTP
# -------------------------------------------------------
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=re_TU_API_KEY_DE_RESEND
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@ingecon.cl
MAIL_FROM_NAME="Ingecon Sistema"

# -------------------------------------------------------
# Cola de trabajos (para EnviarEmailBloqueoJob)
# -------------------------------------------------------
QUEUE_CONNECTION=database
```

> **Nota:** Reemplazar `re_TU_API_KEY_DE_RESEND` con la API key real obtenida desde el dashboard de Resend (https://resend.com/api-keys). El dominio `ingecon.cl` debe estar verificado en Resend.

### Crear tabla de colas (si no existe)

```bash
php artisan queue:table
php artisan migrate
```

### Ejecutar worker de colas en producción

```bash
# Desarrollo
php artisan queue:work --tries=3

# Producción (con Supervisor — ver documentación de despliegue)
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

---

## 9. Layout Principal — Integración del Modal

Para que el modal funcione, debe incluirse en el layout público definido en `05_navegacion_publica.md`.

**Archivo:** `resources/views/layouts/public.blade.php` (sección relevante)

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- CSRF token requerido por el fetch del modal --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ingecon')</title>
    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Alpine.js CDN — defer para que el DOM esté listo --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    {{-- Navbar fija (ver 05_navegacion_publica.md) --}}
    @include('partials.navbar')

    {{-- Sidebar menú lateral (ver 05_navegacion_publica.md) --}}
    @include('partials.sidebar-menu')

    {{-- Modal de login — debe estar fuera del contenido principal --}}
    @include('auth.login-modal')

    {{-- Contenido de cada página --}}
    @yield('content')

    {{-- Stack de scripts (el modal y otros componentes inyectan su <script> aquí) --}}
    @stack('scripts')
</body>
</html>
```

> **Importante:** El modal escucha el evento `abrir-login` despachado desde el ícono de candado en la navbar. El `@include('auth.login-modal')` debe estar en `layouts/public.blade.php`, no en un layout separado `layouts/app.blade.php`.

---

## 10. Resumen de Flujo de Autenticación

```
[Usuario] click "Ingresar al panel"
    → $dispatch('abrir-login')
    → loginModal.abrir()
    → modal Alpine visible

[Usuario] llena correo + password → click "Ingresar"
    → fetch POST /login (JSON)
    → AuthController@login:
        ├─ Validar campos
        ├─ Buscar Administrador por correo
        ├─ Verificar activo / bloqueo temporal
        ├─ Hash::check(password, password_hash [Argon2id])
        │   ├─ INCORRECTO → intentos++ → ¿>= 5? → bloquear 60min + job email
        │   └─ CORRECTO  → crear SESION + cookie httpOnly "id_sesion|token"
        └─ JSON {success, redirect}
    → window.location.href = '/admin/dashboard'

[Request /admin/*]
    → Middleware AdminAuth:
        ├─ Leer cookie 'ingecon_session' → parsear "id_sesion|token"
        ├─ $db->buscarSesionActivaPorId(id_sesion) — consulta O(1) directa
        ├─ Hash::check(token, sesion.token_hash) — verificar solo ese registro
        ├─ Verificar admin activo y no bloqueado
        └─ $request->attributes->set('admin', $admin) → next()

[Usuario] click "Cerrar sesión"
    → POST /logout
    → AuthController@logout:
        ├─ Parsear cookie "id_sesion|token"
        ├─ Sesion::find(id_sesion) → verificar token → estado='cerrada'
        └─ Cookie expirada + redirect '/'
```

---

## 11. Consideraciones de Seguridad

| Aspecto | Implementación |
|---|---|
| Almacenamiento de contraseñas | `Hash::make()` con driver `argon2id` (config/hashing.php) |
| Almacenamiento de tokens de sesión | `Hash::make()` — el token en claro nunca persiste en BD |
| Cookie de sesión | `httpOnly=true`, `SameSite=Strict`, `secure=true` en producción |
| Enumeración de usuarios | Error genérico si el correo no existe (RF28) |
| Bloqueo de fuerza bruta | 5 intentos → 60 minutos de bloqueo (RF34) |
| Validación de requests | `$request->validate()` con reglas estrictas |
| CSRF | Token en `<meta>` enviado en el header `X-CSRF-TOKEN` del fetch |
| Logs de seguridad | `Log::warning` en bloqueos; `Log::info` en envíos de email |
