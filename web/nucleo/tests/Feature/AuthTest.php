<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Administrador;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_login_with_correct_credentials()
    {
        $admin = Administrador::create([
            'correo' => 'test@ingecon.cl',
            'password_hash' => bcrypt('password123'),
            'rol' => 'admin_jefe',
            'activo' => true
        ]);

        $response = $this->post('/login', [
            'correo' => 'test@ingecon.cl',
            'password' => 'password123'
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_admin_cannot_login_with_incorrect_password()
    {
        $admin = Administrador::create([
            'correo' => 'test2@ingecon.cl',
            'password_hash' => bcrypt('password123'),
            'rol' => 'admin_jefe',
            'activo' => true
        ]);

        $response = $this->post('/login', [
            'correo' => 'test2@ingecon.cl',
            'password' => 'wrongpassword'
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('correo');
    }
}