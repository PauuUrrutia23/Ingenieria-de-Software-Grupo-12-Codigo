<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Visitante;

class TermsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** CU2.1 - la página de Términos y Condiciones es accesible */
    public function test_terms_page_is_accessible()
    {
        $response = $this->get('/terminos');
        $response->assertStatus(200);
        $response->assertSee('Términos');
    }

    /** RF04 / CU4.1 - el formulario exige aceptar los Términos y Condiciones */
    public function test_contact_form_requires_terms_checkbox()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Ana',
            'apellido' => 'Soto',
            'email' => 'ana@example.com',
            'mensaje' => 'Consulta de prueba con más de diez caracteres.',
            // sin acepta_terminos
        ]);

        $response->assertSessionHasErrors('acepta_terminos');
    }

    /** RNF05 - un mensaje con HTML/script se guarda tal cual y se escapa al mostrarlo (no se ejecuta) */
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

    /**
     * RNF03 - las secciones administrativas están cerradas para un visitante sin sesión.
     * @dataProvider adminRoutesProvider
     */
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
