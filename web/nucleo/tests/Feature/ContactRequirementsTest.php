<?php

namespace Tests\Feature;

use App\Mail\ConsultaRecibidaMail;
use App\Mail\NuevaConsultaAdminMail;
use App\Models\Administrador;
use App\Models\Consulta;
use App\Services\SendmailAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ContactRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return array_replace([
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'email' => 'ana@example.com',
            'mensaje' => 'Necesito cotizar un galpón industrial.',
            'acepta_terminos' => '1',
        ], $cambios);
    }

    public function test_reglas_de_contacto_rechazan_campos_invalidos(): void
    {
        $this->post('/contacto', $this->datos([
            'nombre' => '123', 'email' => 'invalido', 'mensaje' => 'corto', 'acepta_terminos' => '',
        ]))->assertSessionHasErrors(['nombre', 'email', 'mensaje', 'acepta_terminos']);
        $this->assertDatabaseCount('consultas', 0);
    }

    public function test_apellido_es_opcional_y_confirmacion_refleja_envio_real(): void
    {
        Mail::fake();
        Administrador::factory()->create(['rol' => 'admin', 'activo' => true, 'correo' => 'operador@ingecon.cl']);

        $this->post('/contacto', $this->datos(['apellido' => '']))
            ->assertRedirect('/#contacto')
            ->assertSessionHas('contacto_success', 'Consulta registrada y correo de confirmación enviado.');

        $this->assertDatabaseCount('consultas', 1);
        $this->assertFalse(Consulta::first()->notificacion_admin_pendiente);
        Mail::assertSent(ConsultaRecibidaMail::class, 1);
        Mail::assertSent(NuevaConsultaAdminMail::class, 1);
    }

    public function test_fallo_de_correos_no_elimina_consulta_y_deja_alerta_pendiente(): void
    {
        $adaptador = Mockery::mock(SendmailAdapter::class);
        $adaptador->shouldReceive('enviar')->andThrow(new \RuntimeException('SMTP indisponible'));
        $this->app->instance(SendmailAdapter::class, $adaptador);
        Administrador::factory()->create(['rol' => 'admin_jefe', 'activo' => true, 'correo' => 'jefe@ingecon.cl']);

        $this->post('/contacto', $this->datos())
            ->assertRedirect('/#contacto')
            ->assertSessionHas('contacto_success', 'Consulta registrada, pero no fue posible enviar el correo de confirmación.');

        $this->assertDatabaseCount('consultas', 1);
        $this->assertTrue(Consulta::first()->notificacion_admin_pendiente);
    }

    public function test_sin_administradores_la_alerta_queda_pendiente(): void
    {
        Mail::fake();
        $this->post('/contacto', $this->datos())->assertRedirect('/#contacto');
        $this->assertTrue(Consulta::first()->notificacion_admin_pendiente);
        Mail::assertSent(ConsultaRecibidaMail::class, 1);
        Mail::assertNotSent(NuevaConsultaAdminMail::class);
    }

    public function test_administrador_inactivo_no_recibe_alerta(): void
    {
        Mail::fake();
        Administrador::factory()->create(['rol' => 'admin', 'activo' => false]);
        $this->post('/contacto', $this->datos())->assertRedirect('/#contacto');
        $this->assertTrue(Consulta::first()->notificacion_admin_pendiente);
        Mail::assertNotSent(NuevaConsultaAdminMail::class);
    }
}
