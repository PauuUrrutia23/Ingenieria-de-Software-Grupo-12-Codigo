<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Certificado;
use App\Models\Consulta;
use App\Models\HistorialCambio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// CU 15.3: detalle de un registro del Historial de Cambios.
class HistorialDetalleTest extends TestCase
{
    use RefreshDatabase;

    private Administrador $jefe;
    private Administrador $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->jefe = Administrador::factory()->create(['correo' => 'jefe@ingecon.cl', 'rol' => 'admin_jefe']);
        $this->editor = Administrador::factory()->create([
            'correo' => 'editor@ingecon.cl',
            'perm_editar_contenido' => true, 'perm_exportar_consultas' => true, 'perm_eliminar_consultas' => true,
        ]);
    }

    private function detalleDelUltimo(): array
    {
        $id = HistorialCambio::query()->max('id_historial');
        return $this->loginAdmin($this->jefe)->getJson(route('admin.historial.detalle', $id))->assertOk()->json();
    }

    // El detalle muestra el nombre del campo como en el panel y los valores de sí o no como «Sí» o «No».
    public function test_asignar_un_permiso_muestra_el_campo_y_los_valores_legibles()
    {
        $cuenta = Administrador::factory()->create(['correo' => 'ventas@ingecon.cl']);

        $this->loginAdmin($this->jefe)->patch(route('admin.administradores.permisos', $cuenta->id_admin), [
            'permiso' => 'editar_contenido', 'activo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame([['campo' => 'Editar Contenido', 'anterior' => 'No', 'nuevo' => 'Sí']], $this->detalleDelUltimo()['cambios']);
    }

    public function test_editar_muestra_solo_los_campos_que_cambiaron()
    {
        $cert = Certificado::create(['nombre' => 'NCh 1198', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-15', 'id_admin' => $this->editor->id_admin]);

        $this->loginAdmin($this->editor)->put(route('admin.certificados.update', $cert), [
            'nombre' => 'NCh 1198 Of.2006', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-01-15',
        ])->assertSessionHasNoErrors();

        $detalle = $this->detalleDelUltimo();
        $this->assertSame('editor@ingecon.cl', $detalle['responsable']);
        $this->assertSame('Modificación', $detalle['accion']);
        $this->assertSame('Certificados', $detalle['modulo']);
        $this->assertSame($cert->id_certificado, $detalle['id_registro']);
        $this->assertSame([['campo' => 'Nombre de la Normativa', 'anterior' => 'NCh 1198', 'nuevo' => 'NCh 1198 Of.2006']], $detalle['cambios']);
    }

    public function test_crear_guarda_los_valores_nuevos_y_eliminar_los_anteriores()
    {
        $this->loginAdmin($this->editor)->post(route('admin.certificados.store'), [
            'nombre' => 'NCh 819', 'organismo' => 'INN Chile', 'fecha_emision' => '2024-03-01',
        ])->assertSessionHasNoErrors();
        $this->assertContains(['campo' => 'Nombre de la Normativa', 'anterior' => null, 'nuevo' => 'NCh 819'], $this->detalleDelUltimo()['cambios']);

        $cert = Certificado::query()->firstOrFail();
        $this->loginAdmin($this->editor)->delete(route('admin.certificados.destroy', $cert))->assertSessionHasNoErrors();
        $this->assertContains(['campo' => 'Nombre de la Normativa', 'anterior' => 'NCh 819', 'nuevo' => null], $this->detalleDelUltimo()['cambios']);
    }

    public function test_la_asignacion_de_permiso_muestra_la_casilla_y_la_cuenta_nueva_no_guarda_su_clave()
    {
        $this->loginAdmin($this->jefe)->post(route('admin.administradores.store'), [
            'correo' => 'nuevo@ingeconltda.cl', 'password' => 'ClaveSegura1!', 'password_confirmation' => 'ClaveSegura1!',
        ])->assertSessionHasNoErrors();
        $creacion = json_encode($this->detalleDelUltimo());
        $this->assertStringContainsString('nuevo@ingeconltda.cl', $creacion);
        $this->assertStringNotContainsString('password', $creacion);
        $this->assertStringNotContainsString('$argon', $creacion);

        $nuevo = Administrador::query()->where('correo', 'nuevo@ingeconltda.cl')->firstOrFail();
        $this->loginAdmin($this->jefe)->patch(route('admin.administradores.permisos', $nuevo->id_admin), [
            'permiso' => 'exportar_consultas', 'activo' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame(
            [['campo' => 'Exportar Consultas', 'anterior' => 'No', 'nuevo' => 'Sí']],
            $this->detalleDelUltimo()['cambios']
        );
    }

    public function test_las_consultas_no_exponen_mensaje_ni_datos_del_visitante()
    {
        $consulta = Consulta::factory()->create(['mensaje' => 'Mensaje privado del visitante']);
        $correo = $consulta->visitante->email;

        $this->loginAdmin($this->editor)->getJson(route('admin.consultas.detalle', $consulta->id_consulta))->assertOk();

        $detalle = $this->detalleDelUltimo();
        $this->assertSame([], $detalle['cambios']);
        $this->assertSame($consulta->id_consulta, $detalle['id_registro']);
        $json = json_encode($detalle, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Mensaje privado del visitante', $json);
        $this->assertStringNotContainsString($correo, $json);
    }
}
