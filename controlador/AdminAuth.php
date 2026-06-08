<?php

namespace App\Http\Middleware;

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
    private const COOKIE_NAME = 'ingecon_auth';

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
