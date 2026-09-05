<?php
namespace App\Services;

use App\Mail\ConsultaRecibidaMail;
use App\Mail\CuentaBloqueadaMail;
use App\Mail\PasswordResetLinkMail;
use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService («Control») — Diagrama de Componentes, capa Servicios.
 *
 * Orquesta los tres correos que envía el sistema, delegando la entrega real a
 * SendmailAdapter. Cada método conserva el manejo de excepción exigido por su
 * Caso de Uso: ninguno de estos correos es crítico para la operación principal
 * (bloqueo de cuenta, acuse de consulta, recuperación de contraseña ya quedaron
 * registrados en la Base de Datos antes de llegar acá), así que una falla de
 * correo se registra en el log y no interrumpe el flujo del Visitante/Administrador.
 */
class NotificationService
{
    public function __construct(private SendmailAdapter $sendmail)
    {
    }

    /** CU 33.1 Excepciones 3 y 4: el bloqueo ya se aplicó; si el correo falla, solo se registra. */
    public function notificarBloqueoCuenta(Administrador $admin): void
    {
        try {
            $this->sendmail->enviar($admin->correo, new CuentaBloqueadaMail($admin));
        } catch (\Throwable $e) {
            Log::warning('No se pudo notificar el bloqueo de cuenta.', [
                'id_admin' => $admin->id_admin,
                'motivo' => $e->getMessage(),
            ]);
        }
    }

    /** CU 9.1 / RNF10: la Consulta ya quedó registrada; el acuse es best-effort. */
    public function notificarConsultaRecibida(string $email, Consulta $consulta): void
    {
        try {
            $this->sendmail->enviar($email, new ConsultaRecibidaMail($consulta));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el acuse de recibo de la consulta.', [
                'id_consulta' => $consulta->id_consulta,
                'motivo' => $e->getMessage(),
            ]);
        }
    }

    /**
     * CU 30.1 Excepción 4: el servicio de correo institucional no responde.
     * Lanza la excepción hacia arriba (a diferencia de los otros dos) porque el
     * Controlador necesita distinguir este caso para devolver el mensaje
     * "el envío podría demorar" en vez del genérico de éxito.
     */
    public function notificarRecuperacionPassword(Administrador $admin, string $token): void
    {
        $this->sendmail->enviar($admin->correo, new PasswordResetLinkMail($admin, $token));
    }
}
