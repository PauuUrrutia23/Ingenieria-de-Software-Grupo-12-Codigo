<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialModuleRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_pagina_de_diez_y_redirige_pagina_fuera_de_rango(): void
    {
        $admin = Administrador::factory()->create();
        for ($i = 0; $i < 11; $i++) {
            Consulta::factory()->create(['mensaje' => "Consulta número {$i}"]);
        }

        $this->loginAdmin($admin)->get('/admin/consultas')->assertOk()
            ->assertViewHas('consultas', fn ($p) => $p->count() === 10 && $p->total() === 11);
        $this->get('/admin/consultas?page=999')->assertRedirect('/admin/consultas?q=&orden=desc&page=2');
    }

    public function test_busqueda_y_orden_se_combinan_y_persisten_en_links(): void
    {
        $admin = Administrador::factory()->create();
        $antigua = Consulta::factory()->create(['mensaje' => 'Quiero un galpón', 'created_at' => now()->subDay()]);
        $nueva = Consulta::factory()->create(['mensaje' => 'Quiero otro galpón', 'created_at' => now()]);
        Consulta::factory()->create(['mensaje' => 'Vivienda', 'created_at' => now()]);

        $this->loginAdmin($admin)->get('/admin/consultas?q=galp%C3%B3n&orden=asc')
            ->assertOk()
            ->assertViewHas('consultas', fn ($p) => $p->total() === 2
                && $p->first()->id_consulta === $antigua->id_consulta
                && $p->last()->id_consulta === $nueva->id_consulta)
            ->assertSee('orden=desc', false);
        $this->get('/admin/consultas?orden=incorrecto')->assertOk()
            ->assertViewHas('orden', 'desc');
    }

    public function test_detalle_fresco_controla_consulta_eliminada_y_requiere_sesion(): void
    {
        $consulta = Consulta::factory()->create(['mensaje' => 'Mensaje privado de prueba']);
        $this->getJson("/admin/consultas/{$consulta->id_consulta}/detalle")
            ->assertStatus(401);

        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)->get('/admin/consultas')->assertOk()
            ->assertDontSee('Mensaje privado de prueba', false);
        $this->getJson("/admin/consultas/{$consulta->id_consulta}/detalle")
            ->assertOk()->assertJsonPath('mensaje', 'Mensaje privado de prueba');
        $consulta->delete();
        $this->getJson("/admin/consultas/{$consulta->id_consulta}/detalle")
            ->assertStatus(410);
    }

    public function test_cambio_real_asigna_ultimo_responsable_y_no_op_lo_conserva(): void
    {
        $adminA = Administrador::factory()->create();
        $adminB = Administrador::factory()->create();
        $consulta = Consulta::factory()->create(['estado' => 'pendiente', 'prioridad' => null]);

        $this->loginAdmin($adminA)->put(route('admin.consultas.update', $consulta), [
            'estado' => 'pendiente', 'prioridad' => 'alta',
        ])->assertRedirect();
        $this->assertSame($adminA->id_admin, $consulta->fresh()->id_admin_responsable);

        $this->put(route('admin.consultas.update', $consulta), [
            'estado' => 'pendiente', 'prioridad' => 'alta',
        ])->assertRedirect();
        $this->assertSame($adminA->id_admin, $consulta->fresh()->id_admin_responsable);

        $this->loginAdmin($adminB)->put(route('admin.consultas.update', $consulta), [
            'estado' => 'en_proceso', 'prioridad' => 'alta',
        ])->assertRedirect();
        $this->assertSame($adminB->id_admin, $consulta->fresh()->id_admin_responsable);
    }

    public function test_exportaciones_requieren_sesion_y_generan_archivos(): void
    {
        Consulta::factory()->create(['mensaje' => '=HYPERLINK("https://example.com")']);
        $this->get('/admin/consultas/exportar/csv')->assertRedirect('/login');

        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)->get('/admin/consultas/exportar/csv')
            ->assertOk()->assertHeader('content-disposition');
        $this->get('/admin/consultas/exportar/xlsx')
            ->assertOk()->assertHeader('content-disposition');
    }
}
