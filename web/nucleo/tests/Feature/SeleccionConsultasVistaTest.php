<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RF57, RF58 y RF42: casillas, barra superior y confirmación en el Módulo comercial.
class SeleccionConsultasVistaTest extends TestCase
{
    use RefreshDatabase;

    private const ELIMINAR = 'aria-label="Eliminar consultas seleccionadas"';
    private const EXPORTAR = 'aria-label="Exportar consultas seleccionadas en CSV"';

    private function listado(array $permisos): string
    {
        return $this->loginAdmin(Administrador::factory()->create($permisos))
            ->get(route('admin.consultas.index'))->assertOk()->getContent();
    }

    public function test_cada_consulta_tiene_su_casilla_y_hay_una_general()
    {
        $consulta = Consulta::factory()->create();

        $html = $this->listado([]);

        $this->assertStringContainsString('aria-label="Seleccionar consulta N° ' . $consulta->id_consulta . '"', $html);
        $this->assertStringContainsString('aria-label="Seleccionar todas las consultas de la página"', $html);
    }

    public function test_sin_permisos_se_puede_marcar_pero_no_hay_opciones_de_accion()
    {
        Consulta::factory()->create();

        $html = $this->listado([]);

        $this->assertStringNotContainsString(self::ELIMINAR, $html);
        $this->assertStringNotContainsString(self::EXPORTAR, $html);
    }

    public function test_cada_opcion_aparece_solo_con_su_permiso()
    {
        Consulta::factory()->create();

        $soloExportar = $this->listado(['correo' => 'a@ingecon.cl', 'perm_exportar_consultas' => true]);
        $this->assertStringContainsString(self::EXPORTAR, $soloExportar);
        $this->assertStringNotContainsString(self::ELIMINAR, $soloExportar);

        $soloEliminar = $this->listado(['correo' => 'b@ingecon.cl', 'perm_eliminar_consultas' => true]);
        $this->assertStringContainsString(self::ELIMINAR, $soloEliminar);
        $this->assertStringNotContainsString(self::EXPORTAR, $soloEliminar);
        $this->assertMatchesRegularExpression('/' . preg_quote(self::ELIMINAR, '/') . '[^>]*>\s*<i data-lucide="trash-2"/', $soloEliminar);
    }

    public function test_eliminar_pide_confirmacion_en_una_ventana_modal()
    {
        Consulta::factory()->create();

        $html = $this->listado(['perm_eliminar_consultas' => true]);

        $this->assertStringContainsString('Eliminar Consultas', $html);
        $this->assertStringContainsString('action="' . route('admin.consultas.eliminar') . '"', $html);
        $this->assertStringContainsString('name="ids[]"', $html);
    }

    public function test_ya_no_se_ofrece_exportar_todas()
    {
        Consulta::factory()->create();

        $this->assertStringNotContainsString('Exportar todas las consultas', $this->listado(['perm_exportar_consultas' => true]));
    }
}
