<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:run';
    protected $description = 'Genera un volcado de la base de datos (SQLite/MySQL) y borra las copias de más de 30 días (RNF14).';

    public function handle()
    {
        $connection = config('database.default');
        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$connection}_{$date}";
        $directorio = config('backup.directorio');

        File::ensureDirectoryExists($directorio);
        $path = $directorio . DIRECTORY_SEPARATOR . $filename;

        if ($connection === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (!is_file($dbPath) || !copy($dbPath, $path . '.sqlite')) {
                // Sin respaldo nuevo no se borran las copias antiguas: podrían ser las únicas.
                $this->error('No se pudo crear el respaldo de SQLite.');
                return Command::FAILURE;
            }
            $this->info("Backup de SQLite creado: {$filename}.sqlite");
        } else {
            $mysql = config('database.connections.mysql');
            $archivo = $path . '.sql';

            // La contraseña va en MYSQL_PWD y no en los argumentos, que otros procesos del hosting compartido pueden leer.
            $proceso = new Process(array_merge(config('backup.mysqldump'), [
                '--user=' . $mysql['username'],
                '--host=' . $mysql['host'],
                '--port=' . $mysql['port'],
                '--single-transaction',
                '--result-file=' . $archivo,
                $mysql['database'],
            ]), null, ['MYSQL_PWD' => (string) $mysql['password']]);
            $proceso->setTimeout(600);
            $proceso->run();

            if (!$proceso->isSuccessful()) {
                // Un volcado a medias no sirve como respaldo; las copias antiguas se conservan.
                File::delete($archivo);
                $this->error('No se pudo crear el respaldo de MySQL (código ' . $proceso->getExitCode() . ').');
                return Command::FAILURE;
            }
            $this->info("Backup de MySQL creado: {$filename}.sql");
        }

        $this->borrarCopiasVencidas($directorio);

        return Command::SUCCESS;
    }

    // RNF14: cada copia se conserva como máximo 30 días; la fecha se toma del nombre del archivo.
    private function borrarCopiasVencidas(string $directorio): void
    {
        $limite = Carbon::now()->subDays(config('backup.dias_retencion'));
        foreach (File::files($directorio) as $archivo) {
            if (preg_match('/^backup_\w+_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\./', $archivo->getFilename(), $m)
                && Carbon::createFromFormat('Y-m-d_H-i-s', $m[1])->lte($limite)) {
                File::delete($archivo->getPathname());
            }
        }
    }
}
