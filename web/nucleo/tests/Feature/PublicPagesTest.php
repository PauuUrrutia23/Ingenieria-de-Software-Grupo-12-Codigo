<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Certificado;
use App\Models\Colaborador;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_a_successful_response()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_proyectos_page_returns_a_successful_response()
    {
        $response = $this->get('/proyectos');
        $response->assertStatus(200);
    }

    public function test_certificaciones_page_lists_all_vigentes()
    {
        Certificado::factory()->count(5)->create(['estado' => 'vigente']);
        Certificado::factory()->create(['estado' => 'vencido', 'nombre' => 'Certificado Vencido']);

        $response = $this->get('/certificaciones');

        $response->assertStatus(200);
        $response->assertDontSee('Certificado Vencido');
    }

    public function test_colaboradores_page_lists_registered_colaboradores()
    {
        Colaborador::factory()->create(['nombre_comercial' => 'Proveedor Ejemplo']);

        $response = $this->get('/colaboradores');

        $response->assertStatus(200);
        $response->assertSee('Proveedor Ejemplo');
    }

    public function test_colaboradores_page_shows_empty_state()
    {
        $response = $this->get('/colaboradores');

        $response->assertStatus(200);
        $response->assertSee('Aún no hay colaboradores');
    }

    public function test_404_page_is_customized()
    {
        $response = $this->get('/ruta-inexistente-1234');
        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('no encontrada');
    }
}
