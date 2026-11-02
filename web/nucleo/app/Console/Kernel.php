<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // En cPanel basta un Cron Job cada minuto: php artisan schedule:run
    protected function schedule(Schedule $schedule): void
    {
        // RNF14: respaldo diario de la Base de Datos; el comando borra las copias de más de 30 días.
        $schedule->command('backup:run')->dailyAt('02:00')->withoutOverlapping();
        // RNF24: eliminación automática de Consultas.
        $schedule->command('consultas:depurar')->dailyAt('03:00')->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
