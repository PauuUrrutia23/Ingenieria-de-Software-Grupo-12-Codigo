<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

// RNF24: eliminación automática de Consultas, ejecutada por la tarea programada.
class DepuracionConsultasTest extends TestCase
{
    use RefreshDatabase;

    private Administrador $jefe;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-02 03:00:00');
        $this->jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function consulta(string $estado, string $ultimoCambio): Consulta
    {
        return Consulta::factory()->create([
            'estado' => $estado, 'created_at' => '2026-01-01 10:00:00', 'fecha_cambio_estado' => $ultimoCambio,
        ]);
    }

    private function sigueDisponible(Consulta $consulta): bool
    {
        return $this->loginAdmin($this->jefe)->getJson(route('admin.consultas.detalle', $consulta->id_consulta))->status() === 200;
    }

    public function test_una_finalizada_se_borra_al_cumplir_30_dias_y_no_antes()
    {
        $hace30 = $this->consulta('finalizada', '2026-10-03 03:00:00');
        $hace29 = $this->consulta('finalizada', '2026-10-04 03:00:00');

        Artisan::call('consultas:depurar');

        $this->assertFalse($this->sigueDisponible($hace30));
        $this->assertTrue($this->sigueDisponible($hace29));
    }

    public function test_seis_meses_sin_cambios_de_estado_se_borra_en_cualquier_estado()
    {
        $pendienteVieja = $this->consulta('pendiente', '2026-05-02 03:00:00');
        $enProcesoVieja = $this->consulta('en_proceso', '2026-04-15 09:00:00');
        $pendienteReciente = $this->consulta('pendiente', '2026-05-03 03:00:00');

        Artisan::call('consultas:depurar');

        $this->assertFalse($this->sigueDisponible($pendienteVieja));
        $this->assertFalse($this->sigueDisponible($enProcesoVieja));
        $this->assertTrue($this->sigueDisponible($pendienteReciente));
    }

    public function test_la_depuracion_esta_programada_todos_los_dias_a_las_tres()
    {
        $eventos = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'consultas:depurar'));

        $this->assertCount(1, $eventos);
        $this->assertSame('0 3 * * *', $eventos->first()->expression);
    }

    public function test_borra_al_visitante_sin_otras_consultas_y_conserva_al_que_tiene_otras()
    {
        $soloUna = \App\Models\Visitante::factory()->create(['email' => 'ana@example.com']);
        $conOtra = \App\Models\Visitante::factory()->create(['email' => 'luis@example.com']);
        Consulta::factory()->create(['id_visitante' => $soloUna->id_visitante, 'estado' => 'finalizada', 'fecha_cambio_estado' => '2026-09-01 10:00:00']);
        Consulta::factory()->create(['id_visitante' => $conOtra->id_visitante, 'estado' => 'finalizada', 'fecha_cambio_estado' => '2026-09-01 10:00:00']);
        Consulta::factory()->create(['id_visitante' => $conOtra->id_visitante, 'estado' => 'pendiente', 'fecha_cambio_estado' => '2026-10-30 10:00:00']);

        Artisan::call('consultas:depurar');

        $this->assertFalse(\App\Models\Visitante::query()->where('email', 'ana@example.com')->exists());
        $this->assertTrue(\App\Models\Visitante::query()->where('email', 'luis@example.com')->exists());
    }
}
