<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\ImagenProyecto;
use App\Services\StorageAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProjectRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $imagenes, string $nombre = 'Obra de prueba'): array
    {
        return [
            'nombre_obra' => $nombre,
            'descripcion_tecnica' => 'Descripción técnica de prueba suficientemente larga.',
            'region' => 'Metropolitana',
            'comuna' => 'Santiago',
            'anio_ejecucion' => 2025,
            'categoria' => 'construccion',
            'estado_publicacion' => 'borrador',
            'imagenes' => $imagenes,
        ];
    }

    public function test_imagen_de_hasta_2mb_es_valida()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $ok = UploadedFile::fake()->image('obra.jpg')->size(2000);

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos([$ok], 'Obra 2MB'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('proyectos', ['nombre_obra' => 'Obra 2MB']);
    }

    public function test_imagen_mayor_a_2mb_es_rechazada()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $grande = UploadedFile::fake()->image('obra.jpg')->size(2100);

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos([$grande], 'Obra grande'))
            ->assertSessionHasErrors('imagenes.0');

        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Obra grande']);
    }

    public function test_webp_es_rechazado()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $webp = UploadedFile::fake()->createWithContent('obra.webp', 'contenido-falso');

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos([$webp], 'Obra webp'))
            ->assertSessionHasErrors('imagenes.0');

        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Obra webp']);
    }

    public function test_mas_de_15_imagenes_es_rechazado()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $imagenes = [];
        for ($i = 0; $i < 16; $i++) {
            $imagenes[] = UploadedFile::fake()->image("foto{$i}.jpg");
        }

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos($imagenes, 'Obra 16'))
            ->assertSessionHasErrors('imagenes');

        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Obra 16']);
    }

    public function test_png_y_jpg_se_aceptan_juntos()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $png = UploadedFile::fake()->image('a.png');
        $jpg = UploadedFile::fake()->image('b.jpg');

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos([$png, $jpg], 'Obra mixta'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('proyectos', ['nombre_obra' => 'Obra mixta']);
    }

    public function test_alta_manipulada_con_publicado_se_guarda_como_borrador()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();

        $datos = $this->datos([UploadedFile::fake()->image('x.jpg')], 'Intento publicado');
        $datos['estado_publicacion'] = 'publicado';

        $this->loginAdmin($admin)->post('/admin/proyectos', $datos)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('proyectos', [
            'nombre_obra' => 'Intento publicado',
            'estado_publicacion' => 'borrador',
        ]);
    }

    public function test_formulario_de_alta_no_ofrece_estado_inicial()
    {
        $admin = Administrador::factory()->create();

        $this->loginAdmin($admin)->get('/admin/proyectos/create')
            ->assertStatus(200)
            ->assertDontSee('name="estado_publicacion"', false);
    }

    public function test_alta_sin_imagenes_es_rechazada()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();

        $this->loginAdmin($admin)->post('/admin/proyectos', $this->datos([], 'Sin imagenes'))
            ->assertSessionHasErrors('imagenes');

        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Sin imagenes']);
    }

    public function test_alta_con_una_imagen_es_valida()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();

        $this->loginAdmin($admin)->post('/admin/proyectos', $this->datos([UploadedFile::fake()->image('u.jpg')], 'Una imagen'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('proyectos', ['nombre_obra' => 'Una imagen']);
    }

    public function test_alta_con_quince_imagenes_es_valida()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $imagenes = [];
        for ($i = 0; $i < 15; $i++) {
            $imagenes[] = UploadedFile::fake()->image("f{$i}.jpg");
        }

        $this->loginAdmin($admin)->post('/admin/proyectos', $this->datos($imagenes, 'Quince imagenes'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('proyectos', ['nombre_obra' => 'Quince imagenes']);
        $this->assertSame(15, \App\Models\ImagenProyecto::count());
    }

    public function test_fallo_en_la_primera_imagen_no_deja_proyecto_ni_imagenes()
    {
        $stor = Mockery::mock(StorageAdapter::class);
        $stor->shouldReceive('guardar')->once()->andThrow(new \RuntimeException('disco lleno'));
        $this->app->instance(StorageAdapter::class, $stor);

        $admin = Administrador::factory()->create();

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos([UploadedFile::fake()->image('a.jpg')], 'Falla1'))
            ->assertSessionHasErrors('imagenes');

        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Falla1']);
        $this->assertSame(0, ImagenProyecto::count());
    }

    public function test_fallo_en_la_segunda_imagen_hace_rollback_y_borra_la_primera()
    {
        $stor = Mockery::mock(StorageAdapter::class);
        $stor->shouldReceive('guardar')->once()->andReturn('proyectos/a.jpg');
        $stor->shouldReceive('guardar')->once()->andThrow(new \RuntimeException('disco lleno'));
        $stor->shouldReceive('borrar')->once()->with('proyectos/a.jpg');
        $this->app->instance(StorageAdapter::class, $stor);

        $admin = Administrador::factory()->create();

        $this->loginAdmin($admin)
            ->post('/admin/proyectos', $this->datos([
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ], 'Falla2'))
            ->assertSessionHasErrors('imagenes');

        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Falla2']);
        $this->assertSame(0, ImagenProyecto::count());
    }

    private function proyectoConImagenes(int $adminId, int $nImagenes): \App\Models\Proyecto
    {
        $proyecto = \App\Models\Proyecto::factory()->create(['id_admin' => $adminId]);
        for ($i = 0; $i < $nImagenes; $i++) {
            ImagenProyecto::factory()->create(['id_proyecto' => $proyecto->id_proyecto]);
        }

        return $proyecto;
    }

    public function test_editar_10_mas_6_supera_limite_y_se_rechaza()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $proyecto = $this->proyectoConImagenes($admin->id_admin, 10);

        $nuevas = [];
        for ($i = 0; $i < 6; $i++) {
            $nuevas[] = UploadedFile::fake()->image("n{$i}.jpg");
        }
        $datos = $this->datos($nuevas, 'Ignorado');

        $this->loginAdmin($admin)->put("/admin/proyectos/{$proyecto->id_proyecto}", $datos)
            ->assertSessionHasErrors('imagenes');

        $this->assertSame(10, ImagenProyecto::where('id_proyecto', $proyecto->id_proyecto)->count());
    }

    public function test_editar_10_mas_5_llega_a_15_y_es_valido()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $proyecto = $this->proyectoConImagenes($admin->id_admin, 10);

        $nuevas = [];
        for ($i = 0; $i < 5; $i++) {
            $nuevas[] = UploadedFile::fake()->image("n{$i}.jpg");
        }

        $this->loginAdmin($admin)
            ->put("/admin/proyectos/{$proyecto->id_proyecto}", $this->datos($nuevas))
            ->assertSessionHasNoErrors();

        $this->assertSame(15, ImagenProyecto::where('id_proyecto', $proyecto->id_proyecto)->count());
    }

    public function test_listado_ofrece_categorias_validas_y_edicion_en_modal()
    {
        $admin = Administrador::factory()->create();
        $proyecto = $this->proyectoConImagenes($admin->id_admin, 1);

        $this->loginAdmin($admin)->get('/admin/proyectos')
            ->assertOk()
            ->assertSee('value="construccion"', false)
            ->assertSee('value="industrial"', false)
            ->assertSee('value="terminaciones"', false)
            ->assertSee('name="latitud"', false)
            ->assertSee('name="longitud"', false)
            ->assertSee('Editar Info')
            ->assertSee('abrirEditar('.$proyecto->id_proyecto.')', false);
    }

    public function test_modal_recibe_datos_frescos_y_stale_tiene_mensaje()
    {
        $admin = Administrador::factory()->create();
        $proyecto = $this->proyectoConImagenes($admin->id_admin, 1);
        $proyecto->update(['nombre_obra' => 'Nombre actualizado']);

        $this->get("/admin/proyectos/{$proyecto->id_proyecto}/detalle-edicion")
            ->assertRedirect('/login');
        $this->loginAdmin($admin)
            ->getJson("/admin/proyectos/{$proyecto->id_proyecto}/detalle-edicion")
            ->assertOk()->assertJsonPath('nombre_obra', 'Nombre actualizado')
            ->assertJsonPath('imagenes_count', 1);
        $this->getJson('/admin/proyectos/999999/detalle-edicion')
            ->assertStatus(404)->assertJsonPath('message', 'El proyecto ya no está disponible. Actualice el listado.');
    }

    public function test_fallo_de_archivo_al_editar_revierte_los_cambios()
    {
        $admin = Administrador::factory()->create();
        $proyecto = $this->proyectoConImagenes($admin->id_admin, 1);
        $nombreOriginal = $proyecto->nombre_obra;

        $storage = Mockery::mock(StorageAdapter::class);
        $storage->shouldReceive('guardar')->once()->andReturn('proyectos/nueva.jpg');
        $storage->shouldReceive('guardar')->once()->andThrow(new \RuntimeException('disco lleno'));
        $storage->shouldReceive('borrar')->once()->with('proyectos/nueva.jpg');
        $this->app->instance(StorageAdapter::class, $storage);

        $this->loginAdmin($admin)->put("/admin/proyectos/{$proyecto->id_proyecto}",
            $this->datos([UploadedFile::fake()->image('nueva.jpg'), UploadedFile::fake()->image('otra.jpg')], 'Nombre no persistido'))
            ->assertSessionHasErrors('proyecto');

        $this->assertDatabaseHas('proyectos', ['id_proyecto' => $proyecto->id_proyecto, 'nombre_obra' => $nombreOriginal]);
        $this->assertSame(1, ImagenProyecto::where('id_proyecto', $proyecto->id_proyecto)->count());
    }

    public function test_edicion_no_publica_proyecto_sin_fotografias()
    {
        $admin = Administrador::factory()->create();
        $proyecto = $this->proyectoConImagenes($admin->id_admin, 0);
        $proyecto->update(['estado_publicacion' => 'borrador']);
        $datos = $this->datos([]);
        $datos['estado_publicacion'] = 'publicado';

        $this->loginAdmin($admin)->put("/admin/proyectos/{$proyecto->id_proyecto}", $datos)
            ->assertSessionHasErrors('estado_publicacion');
        $this->assertDatabaseHas('proyectos', [
            'id_proyecto' => $proyecto->id_proyecto,
            'estado_publicacion' => 'borrador',
        ]);
    }

    public function test_visibilidad_de_proyecto_eliminado_muestra_error_funcional()
    {
        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)
            ->patch('/admin/proyectos/999999/visibilidad', ['estado_publicacion' => 'publicado'])
            ->assertRedirect(route('admin.proyectos.index'))
            ->assertSessionHasErrors('estado_publicacion');
    }
}
