<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';
    protected $description = 'Genera un volcado de la base de datos (SQLite/MySQL) y lo guarda en storage.';

    public function handle()
    {
        $connection = config('database.default');
        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$connection}_{$date}";

        if (!Storage::disk('local')->exists('backups')) {
            Storage::disk('local')->makeDirectory('backups');
        }

        $path = storage_path("app/backups/{$filename}");

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            copy($dbPath, $path . '.sqlite');
            $this->info("Backup de SQLite creado: {$filename}.sqlite");
        } else {
            // Asumimos MySQL
            $dbName = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            $command = "mysqldump --user={$user} --password={$password} --host={$host} {$dbName} > {$path}.sql";
            exec($command);
            $this->info("Backup de MySQL creado: {$filename}.sql");
        }

        return Command::SUCCESS;
    }
}