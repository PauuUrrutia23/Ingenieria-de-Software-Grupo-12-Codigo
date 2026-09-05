<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Models\Administrador;
use App\Mail\CuentaBloqueadaMail;
use Carbon\Carbon;

class AccountLockoutTest extends TestCase
{
    use RefreshDatabase;

    /** RF33 / CU33.1 - 5 intentos fallidos bloquean la cuenta 60 minutos y avisan por correo */
    public function test_account_locks_after_5_failed_attempts()
    {
        Mail::fake();
        $admin = Administrador::factory()->create(['correo' => 'admin@ingecon.cl', 'password_hash' => Hash::make('Correcta1!')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['correo' => 'admin@ingecon.cl', 'password' => 'Incorrecta']);
        }

        $admin->refresh();
        $this->assertEquals(5, $admin->intentos_fallidos);
        $this->assertNotNull($admin->bloqueado_hasta);
        $this->assertTrue($admin->bloqueado_hasta->greaterThan(Carbon::now()->addMinutes(59)));
        Mail::assertSent(CuentaBloqueadaMail::class);
    }

    /** CU27.1 Excepción 2 - una cuenta bloqueada rechaza incluso la contraseña correcta */
    public function test_locked_account_rejects_correct_password()
    {
        $admin = Administrador::factory()->create([
            'correo' => 'admin@ingecon.cl',
            'password_hash' => Hash::make('Correcta1!'),
            'bloqueado_hasta' => Carbon::now()->addMinutes(30),
        ]);

        $response = $this->post('/login', ['correo' => 'admin@ingecon.cl', 'password' => 'Correcta1!']);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    /** CU27.1 - un bloqueo ya vencido se limpia y permite iniciar sesión de nuevo */
    public function test_expired_lockout_allows_login_again()
    {
        $admin = Administrador::factory()->create([
            'correo' => 'admin@ingecon.cl',
            'password_hash' => Hash::make('Correcta1!'),
            'bloqueado_hasta' => Carbon::now()->subMinute(),
            'intentos_fallidos' => 5,
        ]);

        $response = $this->post('/login', ['correo' => 'admin@ingecon.cl', 'password' => 'Correcta1!']);

        $this->assertAuthenticated();
    }
}
