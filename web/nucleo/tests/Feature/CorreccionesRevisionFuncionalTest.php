<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Certificado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CorreccionesRevisionFuncionalTest extends TestCase
{
    use RefreshDatabase;

    private function jefe(): Administrador
    {
        return Administrador::create([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);
    }

    public function test_formulario_de_contacto_conserva_lo_escrito_tras_un_error()
    {
        $this->from('/')->post('/contacto', [
            'nombre' => 'Ana123', 'apellido' => 'Rojas', 'email' => 'ana@example.com',
            'mensaje' => 'Mensaje con texto suficiente.', 'acepta_terminos' => '1',
        ])->assertRedirect('/')->assertSessionHasErrors('nombre');

        $this->withSession(['_old_input' => ['nombre' => 'Ana123', 'mensaje' => 'Mensaje con texto suficiente.']])
            ->get('/')->assertStatus(200)
            ->assertSee("nombre: 'Ana123'", false)
            ->assertSee("mensaje: 'Mensaje con texto suficiente.'", false);
    }

    public function test_formulario_de_contacto_trae_las_reglas_para_el_navegador()
    {
        $this->get('/')->assertStatus(200)
            ->assertSee('pattern="[\p{L}\s]+"', false)
            ->assertSee('minlength="10"', false);
    }

    public function test_login_conserva_el_correo_tras_credenciales_invalidas()
    {
        $this->jefe();
        $this->from('/login')->post('/login', ['correo' => 'jefe@ingecon.cl', 'password' => 'Incorrecta1!'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('correo')
            ->assertSessionHasInput('correo', 'jefe@ingecon.cl');
    }

    public function test_organismo_sin_url_no_genera_enlace_vacio()
    {
        $admin = $this->jefe();
        Certificado::create([
            'codigo' => 'QA-1', 'nombre' => 'Norma QA', 'descripcion' => 'x', 'organismo' => 'Organismo QA',
            'url_organismo' => null, 'fecha_emision' => now(), 'estado' => 'vigente', 'id_admin' => $admin->id_admin,
        ]);

        $this->get('/certificaciones')->assertStatus(200)
            ->assertSee('Organismo QA')
            ->assertDontSee('href="#" target="_blank"', false);
    }

    public function test_enlace_de_recuperacion_invalido_informa_el_motivo()
    {
        $this->get('/password/restablecer/token-que-no-existe')
            ->assertStatus(404)
            ->assertSee('El enlace de recuperación no es válido o ya expiró.');
    }

    public function test_error_de_validacion_se_muestra_dentro_del_modal()
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $this->loginAdmin($jefe)
            ->from('/admin/administradores')
            ->post('/admin/administradores', [
                '_modal' => 'crear', 'correo' => 'nuevo@ingecon.cl', 'password' => 'clave123', 'password_confirmation' => 'clave123',
            ])->assertSessionHasErrors('password');

        $html = $this->get('/admin/administradores')->assertStatus(200)->getContent();
        $modal = substr($html, strpos($html, 'Agregar administrador</h3>'));
        $this->assertStringContainsString('role="alert"', substr($modal, 0, 3000));
        $this->assertStringContainsString('No se pudo guardar:', substr($modal, 0, 3000));
    }

    public function test_correo_no_institucional_tiene_mensaje_claro()
    {
        $this->loginAdmin(Administrador::factory()->create(['rol' => 'admin_jefe']))
            ->from('/admin/administradores')
            ->post('/admin/administradores', [
                'correo' => 'persona@gmail.com', 'password' => 'ClaveQa2026!', 'password_confirmation' => 'ClaveQa2026!',
            ])
            ->assertSessionHasErrors(['correo' => 'El correo debe ser un Correo Institucional (@ingecon.cl).']);
    }
}
