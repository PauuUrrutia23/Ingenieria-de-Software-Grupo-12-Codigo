<?php

namespace Tests\Feature;

use App\Services\SendmailAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// RNF23: los registros de errores solo incluyen identificadores y códigos, sin correos, nombres ni mensajes.
class RegistroErroresSinDatosPersonalesTest extends TestCase
{
    use RefreshDatabase;

    private string $archivoLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archivoLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ingecon-rnf23-' . uniqid() . '.log';
        config(['logging.default' => 'single', 'logging.channels.single.path' => $this->archivoLog]);
        Log::forgetChannel('single');
    }

    protected function tearDown(): void
    {
        @unlink($this->archivoLog);
        parent::tearDown();
    }

    private function log(): string
    {
        return is_file($this->archivoLog) ? file_get_contents($this->archivoLog) : '';
    }

    private function enviarConsulta(): void
    {
        $this->post('/contacto', [
            'nombre' => 'Valentina', 'apellido' => 'Quiroga', 'email' => 'valentina.quiroga@example.com',
            'mensaje' => 'Mi proyecto en Pucón necesita cerchas especiales', 'acepta_terminos' => 'on',
        ]);
    }

    public function test_una_falla_de_la_base_no_deja_datos_del_visitante_en_el_log()
    {
        DB::listen(function ($query) {
            // El INSERT del Visitante lleva el correo, el nombre y el apellido.
            if (preg_match('/^insert into .?visitantes/i', $query->sql)) {
                throw new \RuntimeException('Fallo al insertar: ' . json_encode($query->bindings, JSON_UNESCAPED_UNICODE), 1205);
            }
        });

        $this->enviarConsulta();

        $log = $this->log();
        $this->assertStringContainsString('RuntimeException', $log);
        $this->assertStringContainsString('1205', $log);
        foreach (['valentina.quiroga@example.com', 'Valentina', 'Quiroga'] as $dato) {
            $this->assertStringNotContainsString($dato, $log, "El log contiene \"$dato\"");
        }
    }

    public function test_un_fallo_del_correo_no_deja_la_direccion_en_el_log()
    {
        $this->mock(SendmailAdapter::class)->shouldReceive('enviar')
            ->andThrow(new \RuntimeException('550 5.1.1 <valentina.quiroga@example.com>: Recipient address rejected'));

        $this->enviarConsulta();

        $log = $this->log();
        $this->assertStringContainsString('RuntimeException', $log);
        $this->assertStringNotContainsString('valentina.quiroga@example.com', $log);
    }
}
