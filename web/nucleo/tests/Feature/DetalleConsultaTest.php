<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// RF39 y CU 39.1: el detalle muestra la fecha y hora del envío y la constancia de aceptación.
class DetalleConsultaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_detalle_trae_fecha_hora_de_envio_y_constancia()
    {
        $admin = Administrador::factory()->create();
        $consulta = Consulta::factory()->create(['created_at' => Carbon::parse('2026-10-28 14:35:00')]);

        $this->loginAdmin($admin)->getJson(route('admin.consultas.detalle', $consulta->id_consulta))->assertOk()
            ->assertJson(['fecha' => '28/10/2026 14:35', 'acepta_terminos' => true]);
    }

    public function test_el_modal_muestra_la_fecha_de_envio_y_la_constancia()
    {
        $admin = Administrador::factory()->create();

        $this->loginAdmin($admin)->get(route('admin.consultas.index'))->assertOk()
            ->assertSee('Fecha y hora de envío')
            ->assertSee('Aceptó los Términos y Condiciones y la Política de Privacidad')
            ->assertSee('x-show="c.acepta_terminos"', false);
    }
}
