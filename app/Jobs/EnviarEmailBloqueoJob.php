<?php

namespace App\Jobs;

use App\Mail\CuentaBloqueadaMail;
use App\Models\Administrador;
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
     */
    public function handle(): void
    {
        $admin = Administrador::find($this->adminId);

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
