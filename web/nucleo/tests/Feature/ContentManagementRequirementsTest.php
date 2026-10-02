<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Contenido;
use App\Models\Producto;
use App\Http\Controllers\DBRouterController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ContentManagementRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return array_replace([
            'nombre' => 'Viga laminada',
            'descripcion' => 'Producto industrial de madera.',
            'imagen' => UploadedFile::fake()->image('viga.jpg')->size(100),
            'componentes' => ['Pino radiata', 'Adhesivo estructural'],
        ], $cambios);
    }

    public function test_creacion_producto_persiste_imagen_y_componentes(): void
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)->post(route('admin.productos.store'), $this->datos())
            ->assertRedirect(route('admin.productos.index'));

        $producto = Producto::with('componentes')->firstOrFail();
        $this->assertSame('Viga laminada', $producto->nombre);
        $this->assertSame(['Pino radiata', 'Adhesivo estructural'], $producto->componentes->pluck('nombre')->all());
        Storage::disk('public')->assertExists($producto->imagen);
    }

    public function test_creacion_rechaza_archivo_invalido_y_campos_vacios(): void
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)->post(route('admin.productos.store'), $this->datos([
            'nombre' => '', 'descripcion' => '', 'imagen' => UploadedFile::fake()->create('video.webp', 30, 'image/webp'),
        ]))->assertSessionHasErrors(['nombre', 'descripcion', 'imagen']);
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_edicion_sin_nueva_imagen_conserva_archivo_y_sincroniza_componentes(): void
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)->post(route('admin.productos.store'), $this->datos());
        $producto = Producto::firstOrFail();
        $ruta = $producto->imagen;

        $this->put(route('admin.productos.update', $producto->id_producto), [
            'nombre' => 'Viga mejorada', 'descripcion' => 'Nueva descripción',
            'componentes' => ['Componente único'],
        ])->assertRedirect(route('admin.productos.index'));

        $this->assertSame($ruta, $producto->fresh()->imagen);
        $this->assertSame(['Componente único'], $producto->componentes()->pluck('nombre')->all());
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_detalle_eliminado_devuelve_respuesta_funcional(): void
    {
        $admin = Administrador::factory()->create();
        $this->loginAdmin($admin)->getJson('/admin/productos/999999/detalle')
            ->assertStatus(410);
    }

    public function test_alta_contenido_limpia_archivo_si_falla_bd(): void
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $db = Mockery::mock(DBRouterController::class)->makePartial();
        $db->shouldReceive('create')->once()->andThrow(new \RuntimeException('fallo BD'));
        $this->app->instance(DBRouterController::class, $db);

        $this->loginAdmin($admin)->post('/admin/contenido', [
            'seccion' => 'opiniones', 'titulo' => 'Cliente', 'cuerpo' => 'Testimonio',
            'archivo' => UploadedFile::fake()->image('cliente.png'),
        ])->assertSessionHasErrors('contenido');

        $this->assertDatabaseCount('contenidos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('contenido'));
    }

    public function test_edicion_contenido_con_fallo_bd_conserva_archivo_anterior(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('contenido/anterior.png', 'imagen anterior');
        $admin = Administrador::factory()->create();
        $contenido = Contenido::create([
            'seccion' => 'opiniones', 'titulo' => 'Original', 'cuerpo' => 'Texto original',
            'archivo' => 'contenido/anterior.png', 'orden' => 0, 'activo' => true,
            'id_admin' => $admin->id_admin,
        ]);
        $db = Mockery::mock(DBRouterController::class)->makePartial();
        $db->shouldReceive('update')->once()->andThrow(new \RuntimeException('fallo BD'));
        $this->app->instance(DBRouterController::class, $db);

        $this->loginAdmin($admin)->put("/admin/contenido/{$contenido->id_contenido}", [
            'titulo' => 'Editado', 'cuerpo' => 'Texto nuevo',
            'archivo' => UploadedFile::fake()->image('nuevo.png'),
        ])->assertSessionHasErrors('contenido');

        $this->assertSame('Original', $contenido->fresh()->titulo);
        $this->assertSame('contenido/anterior.png', $contenido->fresh()->archivo);
        $this->assertSame(['contenido/anterior.png'], Storage::disk('public')->allFiles('contenido'));
    }
}
