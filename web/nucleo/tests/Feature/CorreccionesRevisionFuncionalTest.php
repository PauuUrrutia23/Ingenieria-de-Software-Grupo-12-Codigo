<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Certificado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Correcciones detectadas en la revisión funcional del sitio contra los RF (Incremento 3).
 */
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

    /** RF06: tras un rechazo del servidor, el formulario conserva lo que escribió el Visitante. */
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

    /** CU 5.1 Exc. 1: el navegador valida el nombre y el largo del mensaje antes de abrir la confirmación. */
    public function test_formulario_de_contacto_trae_las_reglas_para_el_navegador()
    {
        $this->get('/')->assertStatus(200)
            ->assertSee('pattern="[\p{L}\s]+"', false)
            ->assertSee('minlength="10"', false);
    }

    /** RF27 / CU 27.2: el correo escrito se conserva tras credenciales inválidas. */
    public function test_login_conserva_el_correo_tras_credenciales_invalidas()
    {
        $this->jefe();
        $this->from('/login')->post('/login', ['correo' => 'jefe@ingecon.cl', 'password' => 'Incorrecta1!'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('correo')
            ->assertSessionHasInput('correo', 'jefe@ingecon.cl');
    }

    /** RF24: sin URL del organismo, el nombre se muestra como texto y no como enlace a "#". */
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

    /** CU 31.1 Exc. 1-2: un enlace de recuperación inválido informa el motivo. */
    public function test_enlace_de_recuperacion_invalido_informa_el_motivo()
    {
        $this->get('/password/restablecer/token-que-no-existe')
            ->assertStatus(404)
            ->assertSee('El enlace de recuperación no es válido o ya expiró.');
    }

    /** CU 52.1 Exc. 2: el correo no institucional tiene un mensaje claro. */
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
