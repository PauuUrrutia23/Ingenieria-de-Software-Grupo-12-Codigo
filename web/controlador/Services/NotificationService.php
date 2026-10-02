<?php

namespace App\Services;

use App\Mail\ConsultaRecibidaMail;
use App\Mail\NuevaConsultaAdminMail;
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

    public function notificarConsultaRecibida(string $email, Consulta $consulta): bool
    {
        try {
            $this->sendmail->enviar($email, new ConsultaRecibidaMail($consulta));
            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el acuse de recibo de la consulta.', [
                'id_consulta' => $consulta->id_consulta,
                'motivo' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function destinatariosNuevaConsulta()
    {
        return Administrador::query()
            ->where('activo', true)
            ->whereIn('rol', ['admin', 'admin_jefe'])
            ->whereNotNull('correo')
            ->orderBy('id_admin')
            ->get();
    }

    public function notificarNuevaConsultaAdministracion(Consulta $consulta): bool
    {
        try {
            $destinatarios = $this->destinatariosNuevaConsulta();
            if ($destinatarios->isEmpty()) {
                Log::warning('No hay administradores activos para avisar la nueva consulta.', [
                    'id_consulta' => $consulta->id_consulta,
                ]);
                return false;
            }

            $consulta->loadMissing('visitante');
            $todosEnviados = true;
            foreach ($destinatarios as $admin) {
                try {
                    $this->sendmail->enviar($admin->correo, new NuevaConsultaAdminMail($consulta));
                } catch (\Throwable $e) {
                    $todosEnviados = false;
                    Log::warning('No se pudo avisar una nueva consulta a un administrador.', [
                        'id_consulta' => $consulta->id_consulta,
                        'id_admin' => $admin->id_admin,
                        'motivo' => $e->getMessage(),
                    ]);
                }
            }
            return $todosEnviados;
        } catch (\Throwable $e) {
            Log::warning('No se pudieron resolver los destinatarios de una nueva consulta.', [
                'id_consulta' => $consulta->id_consulta,
                'motivo' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function notificarRecuperacionPassword(Administrador $admin, string $token): void
    {
        $this->sendmail->enviar($admin->correo, new PasswordResetLinkMail($admin, $token));
    }
}
