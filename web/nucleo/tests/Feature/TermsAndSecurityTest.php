<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use App\Models\Visitante;

class TermsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_is_accessible()
    {
        $response = $this->get('/terminos');
        $response->assertStatus(200);
        $response->assertSee('Términos');
    }

    public function test_contact_form_requires_terms_checkbox()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Ana',
            'apellido' => 'Soto',
            'email' => 'ana@example.com',
            'mensaje' => 'Consulta de prueba con más de diez caracteres.',
        ]);

        $response->assertSessionHasErrors('acepta_terminos');
    }

    public function test_contact_message_with_html_is_escaped_when_displayed()
    {
        $payload = '<script>alert(1)</script> hola';
        $this->post('/contacto', [
            'nombre' => 'Ana',
            'apellido' => 'Soto',
            'email' => 'ana2@example.com',
            'mensaje' => $payload,
            'acepta_terminos' => 'on',
        ]);

        $this->assertDatabaseHas('consultas', ['mensaje' => $payload]);

        $admin = \App\Models\Administrador::factory()->create();
        $consulta = \App\Models\Consulta::first();
        $response = $this->actingAs($admin)->get("/admin/consultas/{$consulta->id_consulta}");

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    #[DataProvider('adminRoutesProvider')]
    public function test_guest_is_redirected_from_admin_routes($uri)
    {
        $response = $this->get($uri);
        $response->assertRedirect('/login');
    }

    public static function adminRoutesProvider(): array
    {
        return [
            ['/admin/dashboard'],
            ['/admin/proyectos'],
            ['/admin/certificados'],
            ['/admin/colaboradores'],
            ['/admin/consultas'],
            ['/admin/password'],
            ['/admin/contenido'],
        ];
    }
}
