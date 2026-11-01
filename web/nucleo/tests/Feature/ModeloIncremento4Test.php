<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Certificado;
use App\Models\Consulta;
use App\Models\HistorialCambio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModeloIncremento4Test extends TestCase
{
    use RefreshDatabase;

    private function jefe(): Administrador
    {
        return Administrador::firstOrCreate(['correo' => 'jefe@ingecon.cl'], [
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
        ]);
    }

    private function certificado(array $attrs = []): Certificado
    {
        return Certificado::create(array_merge([
            'nombre' => 'NCh 1198',
            'organismo' => 'INN Chile',
            'fecha_emision' => '2024-01-15',
            'id_admin' => $this->jefe()->id_admin,
        ], $attrs));
    }

    public function test_columnas_del_modelo_logico()
    {
        $this->assertTrue(Schema::hasColumn('colaboradores', 'sitio_web'));
        foreach (['acepta_terminos', 'fecha_cambio_estado'] as $columna) {
            $this->assertTrue(Schema::hasColumn('consultas', $columna), "Falta consultas.$columna");
        }
        foreach (['codigo', 'fecha_emision', 'fecha_vencimiento'] as $columna) {
            $this->assertTrue(Schema::hasColumn('certificados', $columna), "Falta certificados.$columna");
        }
        $this->assertFalse(Schema::hasColumn('certificados', 'estado'));
        foreach (['perm_editar_contenido', 'perm_exportar_consultas', 'perm_eliminar_consultas'] as $columna) {
            $this->assertTrue(Schema::hasColumn('administradores', $columna), "Falta administradores.$columna");
        }
        $this->assertTrue(Schema::hasColumns('historial_cambios', [
            'id_historial', 'fecha_hora', 'id_admin', 'correo_responsable', 'accion', 'modulo',
            'valores_anteriores', 'valores_nuevos', 'id_registro_afectado',
        ]));
    }

    public function test_cuenta_nueva_parte_sin_permisos()
    {
        $admin = $this->jefe()->refresh();

        $this->assertFalse($admin->perm_editar_contenido);
        $this->assertFalse($admin->perm_exportar_consultas);
        $this->assertFalse($admin->perm_eliminar_consultas);
    }

    public function test_certificado_recibe_codigo_unico_automatico()
    {
        $primero = $this->certificado();
        $segundo = Certificado::create([
            'nombre' => 'ANSI/TPI', 'organismo' => 'TPI', 'fecha_emision' => '2024-02-01', 'id_admin' => $primero->id_admin,
        ]);

        $this->assertMatchesRegularExpression('/^CERT-\d{4,}$/', $primero->fresh()->codigo);
        $this->assertMatchesRegularExpression('/^CERT-\d{4,}$/', $segundo->fresh()->codigo);
        $this->assertNotSame($primero->fresh()->codigo, $segundo->fresh()->codigo);
    }

    public function test_vigencia_se_calcula_por_fecha_de_vencimiento()
    {
        $sinVencimiento = $this->certificado(['nombre' => 'Sin vencimiento']);
        $vigente = $this->certificado(['nombre' => 'Vence mañana', 'fecha_vencimiento' => Carbon::tomorrow()->toDateString()]);
        $hoy = $this->certificado(['nombre' => 'Vence hoy', 'fecha_vencimiento' => Carbon::today()->toDateString()]);
        $vencido = $this->certificado(['nombre' => 'Venció ayer', 'fecha_vencimiento' => Carbon::yesterday()->toDateString()]);

        $this->assertSame(
            ['Sin vencimiento', 'Vence hoy', 'Vence mañana'],
            Certificado::query()->vigentes()->orderBy('nombre')->pluck('nombre')->all()
        );
        $this->assertSame('vigente', $sinVencimiento->fresh()->estado);
        $this->assertSame('vigente', $hoy->fresh()->estado);
        $this->assertSame('vencido', $vencido->fresh()->estado);

        $this->get('/certificaciones')->assertSee('Vence mañana')->assertDontSee('Venció ayer');
    }

    public function test_vencimiento_anterior_a_la_emision_se_rechaza()
    {
        $admin = $this->jefe();

        $this->loginAdmin($admin)->post(route('admin.certificados.store'), [
            'nombre' => 'Norma con fechas cruzadas',
            'organismo' => 'INN Chile',
            'fecha_emision' => '2024-05-10',
            'fecha_vencimiento' => '2024-05-09',
        ])->assertSessionHasErrors('fecha_vencimiento');

        $this->assertDatabaseMissing('certificados', ['nombre' => 'Norma con fechas cruzadas']);
    }

    public function test_certificado_sin_fecha_de_emision_se_rechaza()
    {
        $admin = $this->jefe();

        $this->loginAdmin($admin)->post(route('admin.certificados.store'), [
            'nombre' => 'Norma sin fecha',
            'organismo' => 'INN Chile',
        ])->assertSessionHasErrors('fecha_emision');
    }

    public function test_formularios_de_certificado_piden_las_fechas_y_no_el_estado()
    {
        $cert = $this->certificado(['fecha_vencimiento' => '2027-03-31']);
        $admin = $this->jefe();

        $this->loginAdmin($admin)->get(route('admin.certificados.index'))->assertOk()
            ->assertSee('name="fecha_emision"', false)
            ->assertSee('name="fecha_vencimiento"', false);

        $this->loginAdmin($admin)->get(route('admin.certificados.edit', $cert))->assertOk()
            ->assertSee('value="2024-01-15"', false)
            ->assertSee('value="2027-03-31"', false)
            ->assertDontSee('name="estado"', false);
    }

    public function test_consulta_del_formulario_guarda_la_aceptacion()
    {
        $this->post('/contacto', [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'email' => 'juan@example.com',
            'mensaje' => 'Necesito cotizar una vivienda industrializada.',
            'acepta_terminos' => 'on',
        ]);

        $consulta = Consulta::query()->firstOrFail();
        $this->assertTrue($consulta->acepta_terminos);
        $this->assertNotNull($consulta->fecha_cambio_estado);
    }

    public function test_fecha_de_cambio_de_estado_solo_cambia_con_el_estado()
    {
        Carbon::setTestNow('2026-11-01 10:00:00');
        $consulta = Consulta::factory()->create(['created_at' => Carbon::now()]);
        $this->assertEquals('2026-11-01 10:00:00', $consulta->fresh()->fecha_cambio_estado->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-11-02 09:00:00');
        $consulta->update(['prioridad' => 'alta']);
        $this->assertEquals('2026-11-01 10:00:00', $consulta->fresh()->fecha_cambio_estado->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-11-03 08:30:00');
        $consulta->update(['estado' => 'finalizada']);
        $this->assertEquals('2026-11-03 08:30:00', $consulta->fresh()->fecha_cambio_estado->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_historial_conserva_el_registro_si_se_elimina_la_cuenta()
    {
        $jefe = $this->jefe();
        $admin = Administrador::create([
            'correo' => 'admin@ingecon.cl', 'password_hash' => Hash::make('ClaveValida1!'), 'rol' => 'admin', 'activo' => true,
        ]);
        $registro = HistorialCambio::create([
            'fecha_hora' => Carbon::now(),
            'id_admin' => $admin->id_admin,
            'correo_responsable' => $admin->correo,
            'accion' => 'eliminacion',
            'modulo' => 'certificados',
            'id_registro_afectado' => 7,
        ]);

        $admin->delete();

        $registro->refresh();
        $this->assertNull($registro->id_admin);
        $this->assertSame('admin@ingecon.cl', $registro->correo_responsable);
        $this->assertNotNull($jefe->fresh());
    }
}
