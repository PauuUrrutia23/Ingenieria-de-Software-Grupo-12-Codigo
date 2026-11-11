<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Colaborador;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Los textos de ayuda del panel no muestran códigos internos de requerimientos (RF, RNF o CU).
class TextosPanelSinCodigosTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_formularios_del_panel_no_muestran_codigos_de_requerimientos()
    {
        $jefe = Administrador::factory()->create(['rol' => 'admin_jefe']);
        $colaborador = Colaborador::factory()->create(['id_admin' => $jefe->id_admin]);
        $ubicacion = Contenido::create(['seccion' => 'ubicacion', 'enlace' => 'https://maps.google.com/?q=Ingecon',
            'orden' => 1, 'activo' => true, 'id_admin' => $jefe->id_admin]);

        $paginas = [
            route('admin.proyectos.index'), route('admin.proyectos.create'),
            route('admin.certificados.index'), route('admin.certificados.create'),
            route('admin.colaboradores.index'), route('admin.colaboradores.create'),
            route('admin.colaboradores.edit', $colaborador->id_colaborador),
            route('admin.contenido.create', ['seccion' => 'ubicacion']),
            route('admin.contenido.create', ['seccion' => 'documentacion']),
            route('admin.contenido.edit', $ubicacion),
        ];

        foreach ($paginas as $url) {
            $html = (string) $this->loginAdmin($jefe)->get($url)->assertOk()->getContent();
            // Lo que ve el usuario: el HTML sin scripts ni comentarios, incluidos los textos que Alpine escribe.
            $visible = preg_replace(['#<script\b.*?</script>#s', '#<!--.*?-->#s'], '', $html);
            $this->assertDoesNotMatchRegularExpression('/\b(RNF|RF|CU)\s?\d+/', $visible, "Código interno visible en $url");
        }
    }
}
