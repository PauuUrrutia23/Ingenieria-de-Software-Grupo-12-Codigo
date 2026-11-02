<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\HistorialCambio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// RF15 y CU 15.1: listado y acceso al Historial de Cambios.
class HistorialListadoTest extends TestCase
{
    use RefreshDatabase;

    private function jefe(): Administrador
    {
        return Administrador::factory()->create(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
    }

    public function test_el_jefe_entra_y_ve_el_estado_vacio()
    {
        $this->loginAdmin($this->jefe())->get(route('admin.historial.index'))->assertOk()
            ->assertSee('Historial de Cambios')
            ->assertSee('Aún no hay registros en el Historial de Cambios.');
    }

    public function test_el_enlace_del_menu_lateral_solo_lo_ve_el_jefe()
    {
        $enlace = 'href="' . route('admin.historial.index') . '"';

        $this->loginAdmin($this->jefe())->get('/admin/dashboard')->assertOk()->assertSee($enlace, false);
        $this->loginAdmin(Administrador::factory()->editorContenido()->create())->get('/admin/dashboard')->assertOk()
            ->assertDontSee($enlace, false);
    }

    public function test_el_registro_se_conserva_e_indica_cuenta_eliminada()
    {
        $jefe = $this->jefe();
        $editor = Administrador::factory()->editorContenido()->create(['correo' => 'editor@ingecon.cl']);
        $this->loginAdmin($editor)->post(route('admin.certificados.store'), [
            'nombre' => 'NCh 1198', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-15',
        ])->assertSessionHasNoErrors();

        $this->loginAdmin($jefe)->delete(route('admin.administradores.destroy', $editor->id_admin))->assertSessionHasNoErrors();

        $html = $this->loginAdmin($jefe)->get(route('admin.historial.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/editor@ingecon\.cl\s*<span[^>]*>Cuenta eliminada<\/span>/', $html);
    }

    public function test_cada_fila_tiene_descripcion_y_ver_detalles_con_su_ventana_modal()
    {
        $jefe = $this->jefe();
        $this->loginAdmin($jefe)->post(route('admin.administradores.store'), [
            'correo' => 'nuevo@ingecon.cl', 'password' => 'ClaveSegura1!', 'password_confirmation' => 'ClaveSegura1!',
        ]);
        $id = HistorialCambio::query()->value('id_historial');

        $this->loginAdmin($jefe)->get(route('admin.historial.index'))->assertOk()
            ->assertSee('Creación en Administración (registro N° ')
            ->assertSee('abrirDetalle(' . $id . ')', false)
            ->assertSee('Ver detalles')
            ->assertSee('Detalle del registro');
    }

    public function test_los_registros_no_se_pueden_modificar_ni_eliminar()
    {
        $registro = HistorialCambio::create([
            'fecha_hora' => now(), 'correo_responsable' => 'jefe@ingecon.cl', 'accion' => 'creacion', 'modulo' => 'certificados',
        ]);

        $this->assertThrows(fn () => $registro->update(['accion' => 'eliminacion']), \LogicException::class);
        $this->assertThrows(fn () => $registro->delete(), \LogicException::class);
        $this->assertSame('creacion', $registro->fresh()->accion);
    }

    public function test_si_la_base_falla_informa_que_no_puede_mostrarse()
    {
        $jefe = $this->jefe();
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (preg_match('/from .?historial_cambios/i', $query->sql)) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $this->loginAdmin($jefe)->get(route('admin.historial.index'))->assertOk()
            ->assertSee('El Historial de Cambios no puede mostrarse temporalmente.');
    }

    public function test_otra_cuenta_no_entra_aunque_tenga_todos_los_permisos()
    {
        $admin = Administrador::factory()->create([
            'perm_editar_contenido' => true, 'perm_exportar_consultas' => true, 'perm_eliminar_consultas' => true,
        ]);

        $this->loginAdmin($admin)->get(route('admin.historial.index'))->assertForbidden();
    }
}
