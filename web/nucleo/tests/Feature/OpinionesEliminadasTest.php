<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RF43: las Opiniones de Clientes se eliminaron del sistema (decisión del equipo del 21-10-2026).
class OpinionesEliminadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_inicio_no_muestra_opiniones()
    {
        $this->get('/')->assertOk()
            ->assertDontSee('Opiniones de clientes')
            ->assertDontSee('id="opiniones"', false);
    }

    public function test_el_panel_no_ofrece_ni_acepta_la_seccion_opiniones()
    {
        $admin = Administrador::factory()->editorContenido()->create();

        $this->loginAdmin($admin)->get(route('admin.contenido.index'))->assertOk()
            ->assertDontSee('Opiniones de Clientes');

        $this->loginAdmin($admin)->post(route('admin.contenido.store'), [
            'seccion' => 'opiniones', 'titulo' => 'Cliente', 'cuerpo' => 'Testimonio',
        ])->assertSessionHasErrors('seccion');
    }
}
