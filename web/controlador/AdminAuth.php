<?php

namespace App\Http\Middleware;

use App\Http\Controllers\DBRouterController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    // Debe coincidir con AuthController::COOKIE_NAME.
    private const COOKIE_NAME = 'ingecon_auth';

    public function __construct(
        private readonly DBRouterController $db
    ) {}

    /**
     * Verifica que la request traiga una cookie de sesión válida y activa.
     * Si lo es, inyecta el Administrador y la Sesión en los atributos del request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $valorCookie = $request->cookie(self::COOKIE_NAME);

        if (! $valorCookie) {
            return $this->rechazar($request, 'Debes iniciar sesión para acceder.');
        }

        $partes = explode('|', $valorCookie, 2);

        if (count($partes) !== 2) {
            return $this->rechazar($request, 'Sesión inválida. Por favor inicia sesión nuevamente.');
        }

        [$idSesion, $token] = $partes;

        // Buscar por ID y verificar solo ese token_hash, sin iterar sesiones.
        $sesion = $this->db->buscarSesionActivaPorId((int) $idSesion);

        if (! $sesion || ! Hash::check($token, $sesion->token_hash)) {
            return $this->rechazar($request, 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
        }

        $admin = $this->db->buscarAdminPorId($sesion->id_admin);

        if (! $admin || ! $admin->activo) {
            return $this->rechazar($request, 'Tu cuenta no tiene acceso al panel de administración.');
        }

        if ($admin->bloqueado_hasta && $admin->bloqueado_hasta->isFuture()) {
            return $this->rechazar($request, 'Tu cuenta está bloqueada temporalmente.');
        }

        $request->attributes->set('admin', $admin);
        $request->attributes->set('sesion', $sesion);

        return $next($request);
    }

    /**
     * Rechaza la request: 401 JSON para fetch/XHR, o redirección a '/' con
     * flash de error para navegación HTML. En ambos casos elimina la cookie.
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
