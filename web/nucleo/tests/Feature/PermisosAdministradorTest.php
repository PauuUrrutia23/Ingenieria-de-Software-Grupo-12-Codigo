<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// RF59, CU 59.1, CU 59.2 y RNF03.
class PermisosAdministradorTest extends TestCase
{
    use RefreshDatabase;

    private function cuenta(array $permisos = []): Administrador
    {
        return Administrador::create(array_merge([
            'correo' => 'admin@ingecon.cl',
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin',
            'activo' => true,
        ], $permisos));
    }

    public function test_sin_editar_contenido_el_servidor_niega_certificados()
    {
        $this->loginAdmin($this->cuenta())
            ->get(route('admin.certificados.index'))
            ->assertForbidden();
    }

    public function test_sin_editar_contenido_el_servidor_niega_todos_los_modulos_de_contenido()
    {
        $cuenta = $this->cuenta();

        foreach (['admin.contenido.index', 'admin.colaboradores.index', 'admin.proyectos.index', 'admin.productos.index'] as $ruta) {
            $this->loginAdmin($cuenta)->get(route($ruta))->assertForbidden();
        }
    }

    public function test_el_jefe_entra_aunque_sus_casillas_esten_en_no()
    {
        $jefe = $this->cuenta(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $consulta = Consulta::factory()->create();

        $this->loginAdmin($jefe)->get(route('admin.certificados.index'))->assertOk();
        $this->loginAdmin($jefe)->get(route('admin.consultas.exportar', ['formato' => 'csv', 'ids' => [$consulta->id_consulta]]))->assertOk();
    }

    public function test_sin_exportar_consultas_el_servidor_niega_la_exportacion()
    {
        $this->loginAdmin($this->cuenta())
            ->get(route('admin.consultas.exportar', 'csv'))
            ->assertForbidden();
    }

    public function test_marcar_y_desmarcar_una_casilla_cambia_el_acceso_de_la_cuenta()
    {
        $jefe = $this->cuenta(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $cuenta = $this->cuenta();
        $ruta = route('admin.administradores.permisos', $cuenta->id_admin);

        $this->loginAdmin($jefe)->patch($ruta, ['permiso' => 'editar_contenido', 'activo' => '1'])->assertRedirect();
        $this->loginAdmin($cuenta->fresh())->get(route('admin.certificados.index'))->assertOk();

        $this->loginAdmin($jefe)->patch($ruta, ['permiso' => 'editar_contenido', 'activo' => '0'])->assertRedirect();
        $this->loginAdmin($cuenta->fresh())->get(route('admin.certificados.index'))->assertForbidden();
    }

    public function test_una_cuenta_normal_no_puede_asignarse_permisos()
    {
        $cuenta = $this->cuenta();
        Consulta::factory()->create();

        $this->loginAdmin($cuenta)
            ->patch(route('admin.administradores.permisos', $cuenta->id_admin), ['permiso' => 'exportar_consultas', 'activo' => '1'])
            ->assertForbidden();
        $this->loginAdmin($cuenta->fresh())->get(route('admin.consultas.exportar', 'csv'))->assertForbidden();
    }

    public function test_las_casillas_de_un_jefe_no_se_modifican()
    {
        $jefe = $this->cuenta(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $otroJefe = $this->cuenta(['correo' => 'jefe2@ingecon.cl', 'rol' => 'admin_jefe']);

        $this->loginAdmin($jefe)
            ->patch(route('admin.administradores.permisos', $otroJefe->id_admin), ['permiso' => 'editar_contenido', 'activo' => '1'])
            ->assertSessionHasErrors('administrador');
    }

    public function test_el_panel_oculta_los_modulos_de_contenido_sin_el_permiso()
    {
        $modulos = ['admin.proyectos.index', 'admin.certificados.index', 'admin.colaboradores.index', 'admin.contenido.index', 'admin.productos.index'];

        $sin = $this->loginAdmin($this->cuenta())->get('/admin/dashboard')->assertOk();
        foreach ($modulos as $ruta) {
            $sin->assertDontSee('href="' . route($ruta) . '"', false);
        }
        $sin->assertSee('href="' . route('admin.consultas.index') . '"', false);

        $con = $this->loginAdmin($this->cuenta(['correo' => 'editor@ingecon.cl', 'perm_editar_contenido' => true]))
            ->get('/admin/dashboard')->assertOk();
        foreach ($modulos as $ruta) {
            $con->assertSee('href="' . route($ruta) . '"', false);
        }
    }

    public function test_el_modulo_comercial_oculta_exportar_sin_el_permiso()
    {
        Consulta::factory()->create();
        $exportar = 'aria-label="Exportar consultas seleccionadas en CSV"';

        $this->loginAdmin($this->cuenta())->get(route('admin.consultas.index'))->assertOk()
            ->assertDontSee($exportar, false);
        $this->loginAdmin($this->cuenta(['correo' => 'ventas@ingecon.cl', 'perm_exportar_consultas' => true]))
            ->get(route('admin.consultas.index'))->assertOk()
            ->assertSee($exportar, false);
    }

    public function test_la_tabla_muestra_una_casilla_por_permiso_marcada_segun_la_cuenta()
    {
        $jefe = $this->cuenta(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $this->cuenta(['correo' => 'ventas@ingecon.cl', 'perm_exportar_consultas' => true]);

        $html = $this->loginAdmin($jefe)->get(route('admin.administradores.index'))->assertOk()
            ->assertSeeInOrder(['Correo Institucional', 'Editar Contenido', 'Exportar Consultas', 'Eliminar Consultas'])
            ->assertSee('Agregar Administrador')
            ->assertSee('Eliminar Cuenta')
            ->getContent();

        $casilla = fn (string $permiso, string $correo) =>
            preg_match('/<input[^>]*type="checkbox"[^>]*aria-label="' . preg_quote("$permiso: $correo", '/') . '"[^>]*>/', $html, $m) ? $m[0] : null;

        $this->assertStringContainsString('checked', (string) $casilla('Exportar Consultas', 'ventas@ingecon.cl'));
        $this->assertStringNotContainsString('checked', (string) $casilla('Editar Contenido', 'ventas@ingecon.cl'));
        $this->assertNotNull($casilla('Eliminar Consultas', 'ventas@ingecon.cl'));
        $this->assertNull($casilla('Editar Contenido', 'jefe@ingecon.cl'));
    }

    public function test_con_editar_contenido_el_servidor_permite_certificados()
    {
        $this->loginAdmin($this->cuenta(['perm_editar_contenido' => true]))
            ->get(route('admin.certificados.index'))
            ->assertOk();
    }
}
