<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Visitante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// RF58 y CU 58.1.
class EliminarConsultasTest extends TestCase
{
    use RefreshDatabase;

    private function cuenta(array $permisos = ['perm_eliminar_consultas' => true]): Administrador
    {
        return Administrador::factory()->create($permisos);
    }

    private function eliminar(Administrador $admin, array $ids): \Illuminate\Testing\TestResponse
    {
        return $this->loginAdmin($admin)->delete(route('admin.consultas.eliminar'), ['ids' => $ids]);
    }

    private function sigueDisponible(Administrador $admin, Consulta $consulta): bool
    {
        return $this->loginAdmin($admin)->getJson(route('admin.consultas.detalle', $consulta->id_consulta))->status() === 200;
    }

    public function test_elimina_solo_las_consultas_seleccionadas()
    {
        $admin = $this->cuenta();
        [$a, $b, $c] = Consulta::factory()->count(3)->create();

        $this->eliminar($admin, [$a->id_consulta, $c->id_consulta])->assertRedirect(route('admin.consultas.index'));

        $this->assertFalse($this->sigueDisponible($admin, $a));
        $this->assertTrue($this->sigueDisponible($admin, $b));
        $this->assertFalse($this->sigueDisponible($admin, $c));
    }

    public function test_borra_al_visitante_sin_otras_consultas_y_conserva_al_que_tiene_otras()
    {
        $admin = $this->cuenta();
        $soloUna = Visitante::factory()->create(['email' => 'ana@example.com']);
        $conDos = Visitante::factory()->create(['email' => 'luis@example.com']);
        $deAna = Consulta::factory()->create(['id_visitante' => $soloUna->id_visitante]);
        $deLuis = Consulta::factory()->create(['id_visitante' => $conDos->id_visitante]);
        $otraDeLuis = Consulta::factory()->create(['id_visitante' => $conDos->id_visitante]);

        $this->eliminar($admin, [$deAna->id_consulta, $deLuis->id_consulta]);

        $this->assertFalse(Visitante::query()->where('email', 'ana@example.com')->exists());
        $this->assertTrue(Visitante::query()->where('email', 'luis@example.com')->exists());
        $this->assertTrue($this->sigueDisponible($admin, $otraDeLuis));
    }

    public function test_informa_las_que_ya_no_estaban_y_elimina_las_restantes()
    {
        $admin = $this->cuenta();
        $vigente = Consulta::factory()->create();
        $yaEliminada = Consulta::factory()->create();
        $idEliminada = $yaEliminada->id_consulta;
        $yaEliminada->delete();

        $this->eliminar($admin, [$vigente->id_consulta, $idEliminada])
            ->assertSessionHas('success', "Se eliminó 1 consulta. La consulta N° $idEliminada ya no estaba disponible.");

        $this->assertFalse($this->sigueDisponible($admin, $vigente));
    }

    public function test_si_la_base_falla_informa_el_error_y_no_borra_nada()
    {
        $admin = $this->cuenta();
        $consulta = Consulta::factory()->create();
        DB::listen(function ($query) {
            if (preg_match('/^delete from .?consultas/i', $query->sql)) {
                throw new \RuntimeException('fallo BD');
            }
        });

        $this->eliminar($admin, [$consulta->id_consulta])->assertSessionHasErrors('consultas');

        $this->assertTrue($this->sigueDisponible($admin, $consulta));
    }

    public function test_sin_el_permiso_el_servidor_niega_y_no_borra()
    {
        $admin = $this->cuenta(['perm_exportar_consultas' => true]);
        $consulta = Consulta::factory()->create();

        $this->eliminar($admin, [$consulta->id_consulta])->assertForbidden();

        $this->assertTrue($this->sigueDisponible($admin, $consulta));
    }
}
