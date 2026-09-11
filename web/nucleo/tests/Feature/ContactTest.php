<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Consulta;
use App\Models\Visitante;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_submit_contact_form()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'email' => 'juan@example.com',
            'mensaje' => 'Hola, me interesa una cotización.',
            'acepta_terminos' => 'on'
        ]);

        $response->assertRedirect('/#contacto');
        $response->assertSessionHas('contacto_success');

        $this->assertDatabaseHas('visitantes', [
            'email' => 'juan@example.com'
        ]);

        $this->assertDatabaseHas('consultas', [
            'mensaje' => 'Hola, me interesa una cotización.'
        ]);
    }

    public function test_contact_form_requires_terms_acceptance()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Juan',
            'email' => 'juan@example.com',
            'mensaje' => 'Hola'
        ]);

        $response->assertSessionHasErrors('acepta_terminos');
        $this->assertDatabaseMissing('visitantes', ['email' => 'juan@example.com']);
    }
}
