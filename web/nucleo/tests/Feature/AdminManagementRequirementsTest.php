<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Producto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_jefe_ve_y_administra_cuentas(): void
    {
        $comun = Administrador::factory()->create(['rol' => 'admin']);
        $this->loginAdmin($comun)->get('/admin/administradores')->assertForbidden();

        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $this->loginAdmin($jefe)->get('/admin/administradores')->assertOk()
            ->assertSee('Agregar administrador');
    }

    public function test_creacion_exige_dominio_y_password_seguro_y_guarda_hash(): void
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $this->loginAdmin($jefe)->post('/admin/administradores', [
            'correo' => 'otro@gmail.com', 'password' => 'débil',
            'password_confirmation' => 'débil',
        ])->assertSessionHasErrors(['correo', 'password']);

        $this->post('/admin/administradores', [
            'correo' => 'nuevo@ingecon.cl', 'password' => 'ClaveSegura1!',
            'password_confirmation' => 'ClaveSegura1!',
        ])->assertRedirect(route('admin.administradores.index'));

        $nuevo = Administrador::where('correo', 'nuevo@ingecon.cl')->firstOrFail();
        $this->assertSame('admin', $nuevo->rol);
        $this->assertTrue($nuevo->activo);
        $this->assertTrue(Hash::check('ClaveSegura1!', $nuevo->password_hash));
    }

    public function test_eliminacion_reasigna_negocio_y_conserva_consulta_sin_responsable(): void
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $objetivo = Administrador::factory()->create(['rol' => 'admin']);
        $proyecto = Proyecto::factory()->create(['id_admin' => $objetivo->id_admin]);
        $producto = Producto::create([
            'nombre' => 'Prueba', 'descripcion' => 'Producto de prueba',
            'imagen' => 'productos/prueba.jpg', 'tipo_mime' => 'image/jpeg',
            'id_admin' => $objetivo->id_admin,
        ]);
        $consulta = Consulta::factory()->create(['id_admin_responsable' => $objetivo->id_admin]);

        $this->loginAdmin($jefe)->delete(route('admin.administradores.destroy', $objetivo->id_admin))
            ->assertRedirect(route('admin.administradores.index'));

        $this->assertNull(Administrador::find($objetivo->id_admin));
        $this->assertSame($jefe->id_admin, $proyecto->fresh()->id_admin);
        $this->assertSame($jefe->id_admin, $producto->fresh()->id_admin);
        $this->assertNull($consulta->fresh()->id_admin_responsable);
    }

    public function test_jefe_no_se_elimina_a_si_mismo_y_stale_es_controlado(): void
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $this->loginAdmin($jefe)->delete(route('admin.administradores.destroy', $jefe->id_admin))
            ->assertSessionHasErrors('administrador');
        $this->delete('/admin/administradores/999999')
            ->assertRedirect(route('admin.administradores.index'));
    }
}
