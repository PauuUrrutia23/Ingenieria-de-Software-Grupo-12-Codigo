<?php

namespace App\Services;

use App\Mail\ConsultaRecibidaMail;
use App\Mail\CuentaBloqueadaMail;
use App\Mail\PasswordResetLinkMail;
use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(private SendmailAdapter $sendmail)
    {
    }

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

    public function notificarRecuperacionPassword(Administrador $admin, string $token): void
    {
        $this->sendmail->enviar($admin->correo, new PasswordResetLinkMail($admin, $token));
    }
}
