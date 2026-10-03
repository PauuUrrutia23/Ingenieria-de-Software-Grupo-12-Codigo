<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicProjectRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function obra(array $datos = []): Proyecto
    {
        return Proyecto::factory()->create(array_merge([
            'nombre_obra' => 'Galpón de prueba',
            'categoria' => 'industrial',
            'region' => 'Biobío',
            'comuna' => 'Concepción',
            'estado_publicacion' => 'publicado',
        ], $datos));
    }

    public function test_busqueda_en_blanco_equivale_a_sin_filtro()
    {
        $this->obra();
        $this->get('/proyectos?q=%20%20%20')->assertStatus(200)->assertSee('Galpón de prueba');
    }

    public function test_busqueda_parcial_y_tres_filtros_se_combinan()
    {
        $this->obra();
        $this->obra(['nombre_obra' => 'Vivienda de prueba', 'categoria' => 'construccion', 'region' => 'Maule']);

        $this->get('/proyectos?q=Concep&categoria=industrial&region=Biobío')
            ->assertStatus(200)->assertSee('Galpón de prueba')->assertDontSee('Vivienda de prueba');
        $this->get('/proyectos?q=Vivienda&categoria=industrial')
            ->assertStatus(200)->assertSee('Sin resultados');
    }

    public function test_categoria_publica_desconocida_se_rechaza()
    {
        $this->getJson('/proyectos?categoria=otra')->assertStatus(422);
    }

    public function test_region_del_menu_solo_procede_de_obras_publicadas()
    {
        $this->obra(['region' => 'Maule']);
        $this->obra(['region' => 'Araucanía', 'estado_publicacion' => 'borrador']);

        $this->get('/proyectos')->assertStatus(200)
            ->assertSee('value="Maule"', false)
            ->assertDontSee('value="Araucanía"', false);
    }

    public function test_detalle_consulta_estado_vigente_e_imagenes_reales()
    {
        Storage::fake('public');
        Storage::disk('public')->put('proyectos/foto.jpg', 'foto');
        $proyecto = $this->obra();
        $proyecto->imagenes()->create([
            'imagen' => 'proyectos/foto.jpg', 'nombre_archivo' => 'foto.jpg',
            'tipo_mime' => 'image/jpeg',
        ]);

        $this->getJson("/proyectos/{$proyecto->id_proyecto}/detalle")
            ->assertStatus(200)
            ->assertJsonPath('nombre', 'Galpón de prueba')
            ->assertJsonPath('categoria', 'Industrial / Pabellones y galpones')
            ->assertJsonCount(1, 'imagenes');

        $proyecto->update(['estado_publicacion' => 'borrador']);
        $this->getJson("/proyectos/{$proyecto->id_proyecto}/detalle")
            ->assertStatus(410)->assertJsonPath('message', 'Este proyecto ya no está disponible.');
    }

    public function test_detalle_eliminado_da_respuesta_funcional()
    {
        $this->getJson('/proyectos/999999/detalle')->assertStatus(410)
            ->assertJsonPath('message', 'Este proyecto ya no está disponible.');
    }

    public function test_marcadores_excluyen_borradores_y_obras_sin_coordenadas()
    {
        $visible = $this->obra(['latitud' => -36.82, 'longitud' => -73.05]);
        $this->obra(['nombre_obra' => 'Sin coordenadas', 'latitud' => null, 'longitud' => null]);
        $this->obra(['nombre_obra' => 'Borrador', 'estado_publicacion' => 'borrador',
            'latitud' => -35.0, 'longitud' => -72.0]);

        $this->getJson('/proyectos/marcadores')->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $visible->id_proyecto);
    }

    public function test_marcadores_respetan_los_filtros_de_la_galeria()
    {
        $this->obra(['latitud' => -36.82, 'longitud' => -73.05]);
        $this->obra(['nombre_obra' => 'Obra del Maule', 'region' => 'Maule',
            'latitud' => -35.0, 'longitud' => -71.0]);

        $this->getJson('/proyectos/marcadores?region=Maule')->assertStatus(200)
            ->assertJsonCount(1)->assertJsonPath('0.nombre', 'Obra del Maule');
    }

    public function test_galeria_tiene_mapa_y_carga_detalle_por_id()
    {
        $proyecto = $this->obra();
        $this->get('/proyectos')->assertStatus(200)
            ->assertSee('data-proyecto-id="'.$proyecto->id_proyecto.'"', false)
            ->assertSee('id="mapa-proyectos"', false)
            ->assertSee('leaflet@1.9.4', false)
            ->assertDontSee('data-proyecto="', false);
    }

    public function test_formularios_admin_incluyen_coordenadas_opcionales()
    {
        $admin = Administrador::factory()->create();
        $proyecto = $this->obra(['id_admin' => $admin->id_admin]);

        $this->loginAdmin($admin)->get('/admin/proyectos/create')->assertStatus(200)
            ->assertSee('name="latitud"', false)->assertSee('name="longitud"', false);
        $this->get("/admin/proyectos/{$proyecto->id_proyecto}/edit")->assertStatus(200)
            ->assertSee('name="latitud"', false)->assertSee('name="longitud"', false);
    }
}
