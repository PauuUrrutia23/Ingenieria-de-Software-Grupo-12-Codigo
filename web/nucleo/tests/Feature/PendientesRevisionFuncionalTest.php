<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Contenido;
use App\Models\Visitante;
use Database\Seeders\EnlacesInstitucionalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendientesRevisionFuncionalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Administrador
    {
        return Administrador::factory()->create(['rol' => 'admin_jefe']);
    }

    public function test_panel_permite_cargar_la_ubicacion_y_se_muestra_en_el_pie()
    {
        $this->loginAdmin($this->admin())
            ->post('/admin/contenido', ['seccion' => 'ubicacion', 'enlace' => 'https://maps.google.com/?q=Planta+Ingecon'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contenidos', ['seccion' => 'ubicacion', 'enlace' => 'https://maps.google.com/?q=Planta+Ingecon']);
        $this->get('/')->assertStatus(200)->assertSee('https://maps.google.com/?q=Planta+Ingecon', false);
    }

    public function test_enlace_invalido_se_rechaza_con_mensaje_claro()
    {
        $this->loginAdmin($this->admin())
            ->post('/admin/contenido', ['seccion' => 'documentacion', 'enlace' => 'javascript:alert(1)'])
            ->assertSessionHasErrors(['enlace' => 'El enlace debe comenzar con http://, https:// o / (archivo del sitio).']);
    }

    public function test_documentacion_de_demostracion_existe_y_el_enlace_la_abre()
    {
        $this->assertFileExists(public_path('docs/conectores-metalicos-ficha-tecnica-demo.pdf'));

        $admin = $this->admin();
        Contenido::create([
            'seccion' => 'documentacion', 'enlace' => 'https://www.strongtie.com/products/connectors',
            'activo' => true, 'orden' => 0, 'id_admin' => $admin->id_admin,
        ]);
        $this->seed(EnlacesInstitucionalesSeeder::class);

        $this->get('/conectores/documentacion')->assertRedirect(EnlacesInstitucionalesSeeder::DOC_DEMO);
        $this->assertDatabaseHas('contenidos', ['seccion' => 'ubicacion']);
    }

    public function test_region_fuera_de_la_lista_predefinida_se_rechaza()
    {
        Storage::fake('public');
        $datos = [
            'nombre_obra' => 'Obra región', 'categoria' => 'industrial', 'anio_ejecucion' => 2025,
            'comuna' => 'Talca', 'descripcion_tecnica' => 'Texto técnico.',
            'imagenes' => [UploadedFile::fake()->image('obra.jpg')],
        ];

        $this->loginAdmin($this->admin())
            ->post('/admin/proyectos', $datos + ['region' => 'Region Inventada'])
            ->assertSessionHasErrors('region');

        $this->post('/admin/proyectos', $datos + ['region' => 'Maule'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('proyectos', ['nombre_obra' => 'Obra región', 'region' => 'Maule']);
    }

    public function test_exportar_sigue_habilitado_aunque_la_busqueda_no_tenga_resultados()
    {
        $visitante = Visitante::create(['nombre' => 'Ana', 'email' => 'ana@example.com']);
        Consulta::create(['id_visitante' => $visitante->id_visitante, 'mensaje' => 'Necesito cotizar cerchas.',
            'estado' => 'pendiente', 'created_at' => now()]);

        $this->loginAdmin($this->admin())
            ->get('/admin/consultas?q=texto-que-no-existe')
            ->assertStatus(200)
            ->assertSee(route('admin.consultas.exportar', 'csv'), false)
            ->assertDontSee('Sin registros para exportar');
    }

    public function test_modulo_usa_el_termino_colaborador()
    {
        $this->loginAdmin($this->admin())
            ->get('/admin/colaboradores')
            ->assertStatus(200)
            ->assertSee('Agregar Colaborador')
            ->assertDontSee('Proveedor');
    }
}
