<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Database\Seeders\AdminJefeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DominioCorreoInstitucionalTest extends TestCase
{
    use RefreshDatabase;

    private function crearCuenta(string $correo)
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);

        return $this->loginAdmin($jefe)->post(route('admin.administradores.store'), [
            'correo' => $correo,
            'password' => 'ClaveValida1!',
            'password_confirmation' => 'ClaveValida1!',
        ]);
    }

    public function test_acepta_un_correo_del_dominio_ingeconltda()
    {
        $this->crearCuenta('ventas@ingeconltda.cl')->assertSessionHasNoErrors();
        $this->assertTrue(Administrador::where('correo', 'ventas@ingeconltda.cl')->exists());
    }

    public function test_rechaza_correos_de_otros_dominios()
    {
        foreach (['ventas@ingecon.cl', 'ventas@gmail.com'] as $correo) {
            $this->crearCuenta($correo)->assertSessionHasErrors('correo');
            $this->assertFalse(Administrador::where('correo', $correo)->exists());
        }
    }

    public function test_el_seeder_crea_la_cuenta_jefe_con_el_correo_configurado()
    {
        config(['app.correo_admin_jefe' => 'guillermo.caceres@ingeconltda.cl']);

        $this->seed(AdminJefeSeeder::class);
        $this->seed(AdminJefeSeeder::class);

        $jefes = Administrador::where('rol', 'admin_jefe')->pluck('correo')->all();
        $this->assertSame(['guillermo.caceres@ingeconltda.cl'], $jefes);
    }
}
