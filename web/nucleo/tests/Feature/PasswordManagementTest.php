<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Models\Administrador;
use App\Models\RecuperacionPassword;
use App\Mail\PasswordResetLinkMail;
use Carbon\Carbon;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_password_with_correct_current_password()
    {
        $admin = Administrador::factory()->create(['password_hash' => Hash::make('Actual123!')]);

        $response = $this->actingAs($admin)->put('/admin/password', [
            'password_actual' => 'Actual123!',
            'password' => 'Nueva123!',
            'password_confirmation' => 'Nueva123!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Nueva123!', $admin->fresh()->password_hash));
    }

    public function test_password_change_rejects_wrong_current_password()
    {
        $admin = Administrador::factory()->create(['password_hash' => Hash::make('Actual123!')]);

        $response = $this->actingAs($admin)->put('/admin/password', [
            'password_actual' => 'Incorrecta1!',
            'password' => 'Nueva123!',
            'password_confirmation' => 'Nueva123!',
        ]);

        $response->assertSessionHasErrors('password_actual');
        $this->assertTrue(Hash::check('Actual123!', $admin->fresh()->password_hash));
    }

    public function test_password_change_rejects_weak_new_password()
    {
        $admin = Administrador::factory()->create(['password_hash' => Hash::make('Actual123!')]);

        $response = $this->actingAs($admin)->put('/admin/password', [
            'password_actual' => 'Actual123!',
            'password' => 'sinnumeros',
            'password_confirmation' => 'sinnumeros',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_forgot_password_generates_token_and_sends_email()
    {
        Mail::fake();
        $admin = Administrador::factory()->create(['correo' => 'admin@ingecon.cl']);

        $response = $this->post('/password/email', ['correo' => 'admin@ingecon.cl']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('recuperaciones_password', 1);
        Mail::assertSent(PasswordResetLinkMail::class);
    }

    public function test_forgot_password_with_unknown_email_does_not_error_or_leak()
    {
        Mail::fake();

        $response = $this->post('/password/email', ['correo' => 'no-existe@ingecon.cl']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('recuperaciones_password', 0);
        Mail::assertNothingSent();
    }

    public function test_expired_reset_token_is_rejected()
    {
        $admin = Administrador::factory()->create();
        $token = 'un-token-de-prueba';
        RecuperacionPassword::create([
            'id_admin' => $admin->id_admin,
            'token_hash' => hash('sha256', $token),
            'expira_en' => Carbon::now()->subMinutes(5),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->get('/password/restablecer/' . $token);

        $response->assertStatus(404);
    }

    public function test_valid_reset_token_updates_password_and_is_invalidated()
    {
        $admin = Administrador::factory()->create();
        $token = 'un-token-valido';
        RecuperacionPassword::create([
            'id_admin' => $admin->id_admin,
            'token_hash' => hash('sha256', $token),
            'expira_en' => Carbon::now()->addMinutes(60),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->post('/password/restablecer', [
            'token' => $token,
            'password' => 'NuevaSegura1!',
            'password_confirmation' => 'NuevaSegura1!',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('NuevaSegura1!', $admin->fresh()->password_hash));

        $response2 = $this->get('/password/restablecer/' . $token);
        $response2->assertStatus(404);
    }
}
