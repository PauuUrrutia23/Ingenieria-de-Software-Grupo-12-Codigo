<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// RNF19 y CU 1.1, Excepciones 7 y 8: protección del Formulario de Contacto.
class ProteccionFormularioContactoTest extends TestCase
{
    use RefreshDatabase;

    private const NOMBRES = ['Ana', 'Bea', 'Carla', 'Dina', 'Elsa', 'Fabia', 'Gala'];

    private function enviar(string $nombre, string $ip = '203.0.113.10', array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/contacto', array_merge([
            'nombre' => $nombre,
            'apellido' => 'Soto',
            'email' => strtolower($nombre) . '@example.com',
            'mensaje' => 'Necesito cotizar un galpón industrial de 400 metros cuadrados.',
            'acepta_terminos' => 'on',
        ], $extra));
    }

    // Nombres de Visitantes visibles en el Módulo comercial.
    private function visitantesRegistrados(): array
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $html = $this->loginAdmin($jefe)->get(route('admin.consultas.index'))->assertOk()->getContent();
        return array_values(array_filter(self::NOMBRES, fn ($n) => str_contains($html, ">$n Soto<")));
    }

    public function test_el_sexto_envio_en_una_hora_desde_la_misma_ip_se_rechaza()
    {
        foreach (array_slice(self::NOMBRES, 0, 5) as $nombre) {
            $this->enviar($nombre)->assertSessionHasNoErrors();
        }

        $this->enviar('Fabia')->assertSessionHasErrors('mensaje');

        $this->assertSame(['Ana', 'Bea', 'Carla', 'Dina', 'Elsa'], $this->visitantesRegistrados());
    }

    public function test_otra_ip_puede_enviar_y_la_misma_vuelve_a_poder_pasada_una_hora()
    {
        foreach (array_slice(self::NOMBRES, 0, 5) as $nombre) {
            $this->enviar($nombre)->assertSessionHasNoErrors();
        }

        $this->enviar('Fabia', '198.51.100.7')->assertSessionHasNoErrors();
        $this->travel(61)->minutes();
        $this->enviar('Gala')->assertSessionHasNoErrors();

        $this->assertSame(self::NOMBRES, $this->visitantesRegistrados());
    }

    public function test_un_envio_con_el_campo_oculto_completo_se_rechaza_sin_registrar()
    {
        $this->enviar('Ana', '203.0.113.10', ['sitio_referencia' => 'https://spam.example'])
            ->assertSessionHasErrors(['mensaje' => 'La solicitud no pudo ser procesada. Intente nuevamente.']);

        $this->assertSame([], $this->visitantesRegistrados());
    }

    public function test_el_formulario_incluye_el_campo_oculto_fuera_de_la_vista()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div[^>]*aria-hidden="true"[^>]*>\s*<label[^>]*>[^<]*<\/label>\s*<input[^>]*name="sitio_referencia"[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="sitio_referencia"[^>]*tabindex="-1"[^>]*autocomplete="off"/', $html);
    }

    public function test_los_envios_con_errores_no_consumen_el_cupo()
    {
        foreach (array_slice(self::NOMBRES, 0, 4) as $nombre) {
            $this->enviar($nombre)->assertSessionHasNoErrors();
        }
        $this->enviar('Elsa', '203.0.113.10', ['mensaje' => 'corto'])->assertSessionHasErrors('mensaje');
        $this->enviar('Elsa', '203.0.113.10', ['mensaje' => 'corto'])->assertSessionHasErrors('mensaje');

        $this->enviar('Elsa')->assertSessionHasNoErrors();
    }
}
