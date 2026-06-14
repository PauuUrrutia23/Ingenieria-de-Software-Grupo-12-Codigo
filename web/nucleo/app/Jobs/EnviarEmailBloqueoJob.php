<?php

namespace App\Jobs;

use App\Mail\CuentaBloqueadaMail;
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

    public int $tries = 3;

    // Segundos de espera entre reintentos.
    public int $backoff = 30;

    public function __construct(
        private readonly int    $adminId,
        private readonly Carbon $momentoBloqueo,
    ) {}

    /**
     * Carga el admin y envía el email de bloqueo. El DBRouterController se
     * resuelve aquí (no por constructor) para no romper la serialización del job.
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
