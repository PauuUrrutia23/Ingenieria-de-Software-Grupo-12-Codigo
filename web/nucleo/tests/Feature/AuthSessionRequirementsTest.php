<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Sesion;
use App\Http\Middleware\CheckAdminJefe;
use App\Http\Middleware\CheckAdminSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * RF27 / RF32 — sesión persistida como autoridad real.
 * FASE 2 del plan: estos tests se escriben ANTES de tocar AuthController (Fase 3)
 * y CheckAdminSession (Fase 4). El bloque B (Fases 2-6) se cierra junto y la suite
 * debe quedar en verde antes de generar checkpoint.
 *
 * Se usa Hash::make() y no bcrypt() a propósito: config/hashing.php fija el driver
 * argon2id, así estos tests no heredan el fallo ambiental de AuthTest.
 */
class AuthSessionRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $extra = []): Administrador
    {
        return Administrador::create(array_merge([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
        ], $extra));
    }

    private function iniciarSesion(Administrador $admin)
    {
        return $this->post('/login', [
            'correo' => $admin->correo,
            'password' => 'ClaveValida1!',
        ]);
    }

    /** Token de sesión que dejó el login (persiste en datos de sesión). */
    private function tokenVigente(): ?string
    {
        return $this->app['session.store']->get('sesion_ingecon');
    }

    public function test_login_creates_a_sesion_record()
    {
        $admin = $this->admin();

        $this->iniciarSesion($admin);

        $this->assertDatabaseCount('sesiones', 1);
        $this->assertDatabaseHas('sesiones', [
            'id_admin' => $admin->id_admin,
            'estado' => 'activa',
        ]);
    }

    public function test_token_hash_is_bound_to_the_persisted_session_token()
    {
        $admin = $this->admin();

        $this->iniciarSesion($admin);

        $sesion = Sesion::where('id_admin', $admin->id_admin)->first();
        $this->assertNotNull($sesion, 'No se registró la sesión.');
        $this->assertNotNull($this->tokenVigente(), 'El login no dejó token en la sesión.');

        // Autoridad: la fila se resuelve por el mismo token que porta la sesión.
        $this->assertSame(
            hash('sha256', $this->tokenVigente()),
            $sesion->token_hash,
            'sesiones.token_hash no corresponde al token de sesión persistido.'
        );
    }

    public function test_closed_session_does_not_grant_panel_access()
    {
        $admin = $this->admin();
        $this->iniciarSesion($admin);

        Sesion::where('id_admin', $admin->id_admin)->update(['estado' => 'cerrada']);

        // La autoridad es la tabla: una fila cerrada expulsa al pedir el panel.
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_missing_session_record_does_not_grant_panel_access()
    {
        $admin = $this->admin();
        $this->iniciarSesion($admin);

        Sesion::where('id_admin', $admin->id_admin)->delete();

        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_active_session_grants_panel_access()
    {
        $admin = $this->admin();
        $this->iniciarSesion($admin);

        // La Fase 3 dejó token en sesión y la fila vigente lo respalda: acceso.
        $this->get('/admin/dashboard')->assertStatus(200);
    }

    public function test_tampered_token_hash_denies_panel_access()
    {
        $admin = $this->admin();
        $this->iniciarSesion($admin);

        Sesion::where('id_admin', $admin->id_admin)
            ->update(['token_hash' => hash('sha256', 'token-ajeno-manipulado')]);

        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_session_row_of_another_admin_denies_access()
    {
        $admin = $this->admin();
        $otro = Administrador::create([
            'correo' => 'otro@ingecon.cl',
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $this->iniciarSesion($admin);

        // La fila pasa a pertenecer a otro admin: el token vigente ya no coincide.
        Sesion::where('id_admin', $admin->id_admin)->update(['id_admin' => $otro->id_admin]);

        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_inactive_admin_cannot_login()
    {
        $admin = $this->admin(['activo' => false]);

        $respuesta = $this->iniciarSesion($admin);

        $this->assertGuest();
        $respuesta->assertSessionHasErrors('correo');
        $this->assertDatabaseCount('sesiones', 0);
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_failed_credentials_do_not_create_a_session()
    {
        $admin = $this->admin();

        $this->post('/login', ['correo' => $admin->correo, 'password' => 'oclaveincorrecta1']);

        $this->assertGuest();
        $this->assertDatabaseCount('sesiones', 0);
    }

    public function test_logout_closes_only_the_current_session()
    {
        $admin = $this->admin();

        // Otra sesión activa del mismo admin (otro dispositivo) no debe verse afectada.
        Sesion::create([
            'id_admin' => $admin->id_admin,
            'token_hash' => hash('sha256', 'token-de-otro-dispositivo'),
            'fecha_inicio' => now(),
            'estado' => 'activa',
        ]);

        $this->iniciarSesion($admin);

        $this->post('/logout')->assertRedirect('/');

        $this->assertDatabaseHas('sesiones', [
            'id_admin' => $admin->id_admin,
            'token_hash' => hash('sha256', 'token-de-otro-dispositivo'),
            'estado' => 'activa',
        ]);
        $this->assertDatabaseHas('sesiones', [
            'id_admin' => $admin->id_admin,
            'estado' => 'cerrada',
        ]);
    }

    public function test_logout_without_persisted_row_does_not_error()
    {
        $admin = $this->admin();
        $this->iniciarSesion($admin);

        Sesion::where('id_admin', $admin->id_admin)->delete();

        $this->post('/logout')->assertRedirect('/');
    }

    public function test_panel_requires_login_after_logout()
    {
        $admin = $this->admin();
        $this->iniciarSesion($admin);

        $this->post('/logout');

        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    /** Registra una ruta privada que exige sesión vigente + rol Administrador Jefe. */
    private function rutaJefe(): void
    {
        $this->app['router']
            ->middleware(['web', 'auth', CheckAdminSession::class, CheckAdminJefe::class])
            ->get('/__ruta_jefe', fn () => response('jefe', 200));
    }

    public function test_admin_jefe_can_access_protected_route()
    {
        $this->rutaJefe();
        $admin = $this->admin(['rol' => 'admin_jefe']);

        $this->loginAdmin($admin)->get('/__ruta_jefe')->assertStatus(200);
    }

    public function test_common_admin_cannot_access_jefe_route()
    {
        $this->rutaJefe();
        $admin = $this->admin(['correo' => 'operador@ingecon.cl', 'rol' => 'admin']);

        $this->loginAdmin($admin)->get('/__ruta_jefe')->assertStatus(403);
    }

    public function test_guest_cannot_access_jefe_route()
    {
        $this->rutaJefe();

        $this->get('/__ruta_jefe')->assertRedirect('/login');
    }

    public function test_inactive_admin_cannot_access_jefe_route()
    {
        $this->rutaJefe();
        $admin = $this->admin(['rol' => 'admin_jefe', 'activo' => false]);

        $this->loginAdmin($admin)->get('/__ruta_jefe')->assertRedirect('/login');
    }
}
