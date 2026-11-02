<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\HistorialCambio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// CU 15.2: filtros del Historial de Cambios.
class HistorialFiltrosTest extends TestCase
{
    use RefreshDatabase;

    private Administrador $jefe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jefe = Administrador::factory()->create(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $registros = [
            ['2026-10-01 09:00:00', 'ana@ingecon.cl', 'creacion', 'certificados', 1],
            ['2026-10-05 10:00:00', 'ana@ingecon.cl', 'modificacion', 'proyectos', 2],
            ['2026-10-10 11:00:00', 'luis@ingecon.cl', 'asignacion_permiso', 'administradores', 3],
            ['2026-10-15 12:00:00', 'luis@ingecon.cl', 'exportacion', 'consultas', null],
            ['2026-10-20 13:00:00', 'ana@ingecon.cl', 'eliminacion', 'consultas', 4],
            ['2026-10-25 14:00:00', 'luis@ingecon.cl', 'visualizacion', 'consultas', 5],
        ];
        foreach ($registros as [$fecha, $correo, $accion, $modulo, $id]) {
            HistorialCambio::create([
                'fecha_hora' => $fecha, 'correo_responsable' => $correo, 'accion' => $accion,
                'modulo' => $modulo, 'id_registro_afectado' => $id,
            ]);
        }
    }

    // Descripción de cada fila mostrada, desde la más reciente.
    private function filtrar(array $criterios): array
    {
        $html = $this->loginAdmin($this->jefe)->get(route('admin.historial.index', $criterios))->assertOk()->getContent();
        preg_match_all('/<tr data-registro[^>]*>.*?<td[^>]*>.*?<\/td>.*?<td[^>]*>.*?<\/td>.*?<td[^>]*>.*?<\/td>.*?<td[^>]*>.*?<\/td>\s*<td[^>]*>(.*?)<\/td>/s', $html, $m);
        return array_map(fn ($d) => trim($d), $m[1]);
    }

    public function test_accesos_rapidos_por_tipo_de_accion()
    {
        $this->assertSame(['Exportación en Consultas'], $this->filtrar(['tipo' => 'exportaciones']));
        $this->assertSame(['Eliminación en Consultas (registro N° 4)'], $this->filtrar(['tipo' => 'eliminaciones']));
        $this->assertSame(
            ['Asignación de permiso en Administración (registro N° 3)', 'Modificación en Proyectos (registro N° 2)'],
            $this->filtrar(['tipo' => 'modificaciones'])
        );
        $this->assertSame(['Creación en Certificados (registro N° 1)'], $this->filtrar(['tipo' => 'creaciones']));
        $this->assertSame(['Vista de detalle en Consultas (registro N° 5)'], $this->filtrar(['tipo' => 'visualizaciones']));
        $this->assertCount(6, $this->filtrar(['tipo' => 'todos']));
    }

    public function test_responsable_modulo_y_texto_se_combinan_con_el_tipo()
    {
        $this->assertSame(
            ['Vista de detalle en Consultas (registro N° 5)', 'Exportación en Consultas'],
            $this->filtrar(['responsable' => 'luis@ingecon.cl', 'modulo' => 'consultas'])
        );
        $this->assertSame(['Eliminación en Consultas (registro N° 4)'], $this->filtrar(['modulo' => 'consultas', 'tipo' => 'eliminaciones']));
        // El texto busca en el administrador, el módulo o la acción.
        $this->assertCount(3, $this->filtrar(['q' => 'luis']));
        $this->assertSame(['Modificación en Proyectos (registro N° 2)'], $this->filtrar(['q' => 'proyectos']));
        $this->assertSame(['Exportación en Consultas'], $this->filtrar(['q' => 'exportación']));
    }

    public function test_rango_de_fechas_incluye_ambos_dias()
    {
        $this->assertSame(
            ['Eliminación en Consultas (registro N° 4)', 'Exportación en Consultas', 'Asignación de permiso en Administración (registro N° 3)'],
            $this->filtrar(['desde' => '2026-10-10', 'hasta' => '2026-10-20'])
        );
    }

    public function test_desde_posterior_a_hasta_se_rechaza_y_resalta_el_rango()
    {
        $this->loginAdmin($this->jefe)->get(route('admin.historial.index', ['desde' => '2026-10-20', 'hasta' => '2026-10-10']))
            ->assertSessionHasErrors('desde');
    }

    public function test_muestra_la_cantidad_y_el_estado_vacio_conserva_los_criterios()
    {
        $this->loginAdmin($this->jefe)->get(route('admin.historial.index', ['modulo' => 'consultas']))->assertOk()
            ->assertSee('3 registros encontrados');

        $this->loginAdmin($this->jefe)->get(route('admin.historial.index', ['q' => 'nadie@ingecon.cl']))->assertOk()
            ->assertSee('Ningún registro cumple los criterios de filtrado.')
            ->assertSee('value="nadie@ingecon.cl"', false)
            ->assertSee('Limpiar');
    }
}
