<?php
namespace App\Http\Controllers;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class QaController extends Controller
{
    /**
     * Página con el botón "Ejecutar pruebas" y la lista en vivo de resultados.
     */
    public function index()
    {
        return view('admin.qa.index');
    }

    /**
     * Corre "php artisan test" como subproceso real y transmite cada línea
     * de salida al navegador a medida que se produce (Server-Sent Events),
     * para que se vea la ejecución en vivo en vez de un resultado final.
     */
    public function stream()
    {
        return response()->stream(function () {
            $phpBinary = PHP_BINARY;
            $artisan = base_path('artisan');

            // En Windows, Symfony Process escribe archivos temporales de tubería en
            // sys_get_temp_dir(); si eso resuelve a C:\WINDOWS sin permisos de escritura,
            // el subproceso falla antes de correr ningún test. Se fuerza una carpeta propia.
            $tmpDir = storage_path('app/qa-tmp');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }
            // "php artisan test" arranca a su vez un subproceso propio (Pest/PHPUnit) y ese
            // hereda el entorno vía $_SERVER/$_ENV, no vía getenv() — hay que fijar los tres.
            foreach (['TMP', 'TEMP'] as $var) {
                putenv($var . '=' . $tmpDir);
                $_ENV[$var] = $tmpDir;
                $_SERVER[$var] = $tmpDir;
            }

            $process = new Process([$phpBinary, $artisan, 'test', '--colors=never'], base_path());
            $process->setTimeout(180);

            $send = function (array $payload) {
                echo 'data: ' . json_encode($payload) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $send(['type' => 'start']);

            try {
                $process->run(function ($type, $buffer) use ($send) {
                    foreach (preg_split('/\r\n|\r|\n/', $buffer) as $line) {
                        if (trim($line) === '') {
                            continue;
                        }
                        $send(['type' => 'line', 'stream' => $type, 'text' => $line]);
                    }
                });
            } catch (ProcessTimedOutException $e) {
                $send(['type' => 'line', 'stream' => 'err', 'text' => 'La ejecución superó el tiempo máximo (180s).']);
            }

            $send([
                'type' => 'done',
                'exitCode' => $process->getExitCode(),
                'success' => $process->isSuccessful(),
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }
}
