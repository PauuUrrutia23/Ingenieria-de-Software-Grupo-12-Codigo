<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RF42, CU 42.1 y 42.2: se exportan únicamente las Consultas seleccionadas.
class ExportarSeleccionadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_archivo_contiene_solo_las_seleccionadas()
    {
        $admin = Administrador::factory()->exportadorConsultas()->create();
        $a = Consulta::factory()->create(['mensaje' => 'Cotizar galpón de 400 m2']);
        Consulta::factory()->create(['mensaje' => 'Consulta que no se seleccionó']);
        $c = Consulta::factory()->create(['mensaje' => 'Escalera prefabricada']);

        $respuesta = $this->loginAdmin($admin)
            ->get(route('admin.consultas.exportar', ['formato' => 'csv', 'ids' => [$a->id_consulta, $c->id_consulta]]))
            ->assertOk();
        $csv = file_get_contents($respuesta->baseResponse->getFile()->getPathname());

        $this->assertStringContainsString('Cotizar galpón de 400 m2', $csv);
        $this->assertStringContainsString('Escalera prefabricada', $csv);
        $this->assertStringNotContainsString('Consulta que no se seleccionó', $csv);
    }

    public function test_sin_seleccion_no_se_exporta()
    {
        $admin = Administrador::factory()->exportadorConsultas()->create();
        Consulta::factory()->create();

        $this->loginAdmin($admin)->get(route('admin.consultas.exportar', 'csv'))
            ->assertSessionHasErrors('exportar');
    }
}
