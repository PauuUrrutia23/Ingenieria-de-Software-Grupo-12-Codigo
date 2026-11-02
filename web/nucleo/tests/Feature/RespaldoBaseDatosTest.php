<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

// RNF14: respaldo diario de la Base de Datos, conservando cada copia por un máximo de 30 días.
class RespaldoBaseDatosTest extends TestCase
{
    private string $directorio;
    private string $baseSqlite;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-02 02:00:00');
        $this->directorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ingecon-respaldos-' . uniqid();
        File::makeDirectory($this->directorio);
        $this->baseSqlite = $this->directorio . '-base.sqlite';
        file_put_contents($this->baseSqlite, 'contenido de la base');
        config([
            'backup.directorio' => $this->directorio,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->baseSqlite,
        ]);
    }

    protected function tearDown(): void
    {
        unset($_ENV['MYSQLDUMP_FALSO_REGISTRO'], $_ENV['MYSQLDUMP_FALSO_FALLA']);
        File::deleteDirectory($this->directorio);
        @unlink($this->baseSqlite);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function copias(): array
    {
        $nombres = array_map('basename', File::files($this->directorio));
        sort($nombres);
        return $nombres;
    }

    public function test_crea_la_copia_y_borra_las_de_30_dias_o_mas()
    {
        touch($this->directorio . '/backup_sqlite_2026-10-03_02-00-00.sqlite');
        touch($this->directorio . '/backup_sqlite_2026-10-04_02-00-00.sqlite');

        $this->assertSame(0, Artisan::call('backup:run'));

        $this->assertSame([
            'backup_sqlite_2026-10-04_02-00-00.sqlite',
            'backup_sqlite_2026-11-02_02-00-00.sqlite',
        ], $this->copias());
        $this->assertSame('contenido de la base', file_get_contents($this->directorio . '/backup_sqlite_2026-11-02_02-00-00.sqlite'));
    }

    public function test_si_el_respaldo_falla_no_borra_las_copias_antiguas()
    {
        touch($this->directorio . '/backup_sqlite_2026-09-01_02-00-00.sqlite');
        config(['database.connections.sqlite.database' => $this->directorio . '/no-existe.sqlite']);

        $this->assertSame(1, Artisan::call('backup:run'));

        $this->assertSame(['backup_sqlite_2026-09-01_02-00-00.sqlite'], $this->copias());
    }

    public function test_el_respaldo_esta_programado_todos_los_dias_a_las_dos()
    {
        $eventos = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'backup:run'));

        $this->assertCount(1, $eventos);
        $this->assertSame('0 2 * * *', $eventos->first()->expression);
    }

    // Configura MySQL con un mysqldump falso que anota en $registro cómo fue llamado.
    private function usarMysqlConDumpFalso(string $registro, bool $falla = false): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(config('database.connections.mysql'), [
                'host' => '127.0.0.1', 'port' => '3306', 'database' => 'ingecon',
                'username' => 'ingecon_app', 'password' => 'ClaveSecreta123',
            ]),
            'backup.mysqldump' => [PHP_BINARY, base_path('tests/Fixtures/mysqldump-falso.php')],
        ]);
        // Symfony Process hereda el entorno desde $_ENV, no desde putenv().
        $_ENV['MYSQLDUMP_FALSO_REGISTRO'] = $registro;
        $_ENV['MYSQLDUMP_FALSO_FALLA'] = $falla ? '1' : '';
    }

    public function test_mysql_recibe_la_clave_por_entorno_y_no_por_argumentos()
    {
        $registro = $this->directorio . '-registro.json';
        $this->usarMysqlConDumpFalso($registro);

        $this->assertSame(0, Artisan::call('backup:run'));

        $llamada = json_decode(file_get_contents($registro), true);
        @unlink($registro);
        $this->assertStringNotContainsString('ClaveSecreta123', implode(' ', $llamada['argumentos']));
        $this->assertSame('ClaveSecreta123', $llamada['mysql_pwd']);
        $this->assertSame(['backup_mysql_2026-11-02_02-00-00.sql'], $this->copias());
    }

    public function test_si_mysqldump_falla_no_deja_un_respaldo_incompleto()
    {
        touch($this->directorio . '/backup_mysql_2026-09-01_02-00-00.sql');
        $registro = $this->directorio . '-registro.json';
        $this->usarMysqlConDumpFalso($registro, falla: true);

        $this->assertSame(1, Artisan::call('backup:run'));

        @unlink($registro);
        $this->assertSame(['backup_mysql_2026-09-01_02-00-00.sql'], $this->copias());
    }
}
