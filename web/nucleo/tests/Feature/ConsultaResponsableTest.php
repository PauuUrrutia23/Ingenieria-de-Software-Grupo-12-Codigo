<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Visitante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * FASE 10 — RF53 / CU40.2: la consulta sobrevive a la eliminación del responsable.
 */
class ConsultaResponsableTest extends TestCase
{
    use RefreshDatabase;

    private function crearConsulta(?int $responsable): Consulta
    {
        $visitante = Visitante::create([
            'nombre' => 'Juan',
            'apellido' => 'Piedra',
            'email' => 'jpiedra@example.com',
        ]);

        return Consulta::create([
            'mensaje' => 'Quiero cotizar cerchas.',
            'estado' => 'pendiente',
            'id_visitante' => $visitante->id_visitante,
            'id_admin_responsable' => $responsable,
            'created_at' => now(),
        ]);
    }

    public function test_eliminar_admin_deja_la_consulta_con_responsable_null()
    {
        $admin = Administrador::create([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin',
            'activo' => true,
        ]);

        $consulta = $this->crearConsulta($admin->id_admin);
        $this->assertSame($admin->id_admin, $consulta->id_admin_responsable);

        DB::table('administradores')->where('id_admin', $admin->id_admin)->delete();

        $consultaRefrescada = Consulta::find($consulta->id_consulta);
        $this->assertNotNull($consultaRefrescada, 'La consulta no debe eliminarse con su responsable.');
        $this->assertNull($consultaRefrescada->id_admin_responsable, 'El responsable debe quedar en NULL.');
    }

    public function test_notificacion_admin_estado_por_defecto_y_mutable()
    {
        $consulta = $this->crearConsulta(null);

        $consulta->refresh();
        $this->assertFalse($consulta->notificacion_admin_pendiente, 'Default debe ser false.');
        $this->assertNull($consulta->notificacion_admin_ultimo_error);

        $consulta->update(['notificacion_admin_pendiente' => true]);
        $this->assertTrue($consulta->fresh()->notificacion_admin_pendiente);

        $consulta->update(['notificacion_admin_ultimo_error' => 'SMTP fuera de servicio']);
        $this->assertSame('SMTP fuera de servicio', $consulta->fresh()->notificacion_admin_ultimo_error);
    }
}
