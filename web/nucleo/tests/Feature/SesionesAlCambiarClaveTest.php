<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\RecuperacionPassword;
use App\Models\Sesion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

// RNF21, CU 28.2 y CU 31.2: al cambiar o restablecer la contraseña se cierran las demás sesiones.
class SesionesAlCambiarClaveTest extends TestCase
{
    use RefreshDatabase;

    private Administrador $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Administrador::factory()->create(['password_hash' => Hash::make('ClaveActual1!')]);
    }

    // Abre una sesión de la cuenta, como si fuera otro dispositivo, y devuelve su token.
    private function abrirSesion(): string
    {
        $token = Str::random(48);
        Sesion::create([
            'id_admin' => $this->admin->id_admin, 'token_hash' => hash('sha256', $token),
            'fecha_inicio' => Carbon::now(), 'estado' => 'activa',
        ]);
        return $token;
    }

    private function usarSesion(string $token): static
    {
        $this->app['session.store']->put('sesion_ingecon', $token);
        return $this->actingAs($this->admin);
    }

    private function sigueAbierta(string $token): bool
    {
        return $this->usarSesion($token)->get('/admin/dashboard')->status() === 200;
    }

    public function test_cambiar_la_clave_cierra_las_otras_sesiones_y_conserva_la_actual()
    {
        $actual = $this->abrirSesion();
        $otra = $this->abrirSesion();

        $this->usarSesion($actual)->put(route('admin.password.update'), [
            'password_actual' => 'ClaveActual1!', 'password' => 'ClaveNueva1!', 'password_confirmation' => 'ClaveNueva1!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($this->sigueAbierta($actual));
        $this->assertFalse($this->sigueAbierta($otra));
    }

    public function test_restablecer_con_el_enlace_cierra_todas_las_sesiones()
    {
        $primera = $this->abrirSesion();
        $segunda = $this->abrirSesion();
        $enlace = Str::random(64);
        RecuperacionPassword::create([
            'id_admin' => $this->admin->id_admin, 'token_hash' => hash('sha256', $enlace),
            'expira_en' => Carbon::now()->addMinutes(30), 'created_at' => Carbon::now(),
        ]);

        $this->post(route('password.update'), [
            'token' => $enlace, 'password' => 'ClaveNueva1!', 'password_confirmation' => 'ClaveNueva1!',
        ])->assertRedirect('/login')->assertSessionHasNoErrors();

        $this->assertFalse($this->sigueAbierta($primera));
        $this->assertFalse($this->sigueAbierta($segunda));
    }
}
