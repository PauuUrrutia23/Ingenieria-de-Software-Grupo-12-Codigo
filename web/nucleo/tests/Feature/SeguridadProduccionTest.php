<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\PasswordResetLinkMail;
use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SeguridadProduccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_produccion_el_enlace_de_restablecer_usa_el_dominio_del_sitio()
    {
        Mail::fake();
        config(['app.url' => 'https://ingeconltda.cl']);
        $this->app['env'] = 'production';
        Administrador::factory()->create(['correo' => 'jefe@ingeconltda.cl']);

        $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post('http://sitio-del-atacante.com/password/email', ['correo' => 'jefe@ingeconltda.cl']);

        Mail::assertSent(PasswordResetLinkMail::class);
        Mail::assertSent(PasswordResetLinkMail::class, function (PasswordResetLinkMail $correo) {
            $html = $correo->render();

            return str_contains($html, 'https://ingeconltda.cl/password/restablecer/')
                && !str_contains($html, 'sitio-del-atacante');
        });
    }

    private const CORREO_CON_SALTO = "\"ana\r\nBcc: espia@otro-dominio.com\r\n\"@gmail.com";

    public function test_el_formulario_de_contacto_rechaza_un_correo_con_salto_de_linea()
    {
        Mail::fake();
        $datos = ['nombre' => 'Ana', 'mensaje' => 'Quisiera una cotización de cerchas.', 'acepta_terminos' => '1'];

        $this->post(route('contacto.store'), $datos + ['email' => self::CORREO_CON_SALTO])->assertSessionHasErrors('email');
        $this->assertSame(0, Consulta::count());

        $this->post(route('contacto.store'), $datos + ['email' => 'ana@gmail.com'])->assertSessionHasNoErrors();
        $this->assertSame(1, Consulta::count());
    }

    public function test_la_creacion_de_cuentas_rechaza_un_correo_con_salto_de_linea()
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $correo = "\"ana\r\nBcc: espia@otro-dominio.com\r\n\"@ingeconltda.cl";

        $this->loginAdmin($jefe)->post(route('admin.administradores.store'), [
            'correo' => $correo,
            'password' => 'ClaveValida1!',
            'password_confirmation' => 'ClaveValida1!',
        ])->assertSessionHasErrors('correo');

        $this->assertFalse(Administrador::where('correo', $correo)->exists());
    }
}
