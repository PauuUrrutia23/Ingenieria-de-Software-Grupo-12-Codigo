<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Contenido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// RNF17 y CU 43.2–43.5, Excepción 1: el Panel de gestión solo acepta JPG, JPEG o PNG de hasta 2 MB.
class ArchivosPanelGestionTest extends TestCase
{
    use RefreshDatabase;

    private function crear(UploadedFile $archivo)
    {
        Storage::fake('public');

        return $this->loginAdmin(Administrador::factory()->editorContenido()->create())
            ->post(route('admin.contenido.store'), [
                'seccion' => 'banner',
                'titulo' => 'Planta Coronel',
                'cuerpo' => 'Texto descriptivo del banner.',
                'archivo' => $archivo,
            ]);
    }

    public function test_rechaza_una_imagen_webp()
    {
        $this->crear(UploadedFile::fake()->image('banner.webp')->size(100))
            ->assertSessionHasErrors('archivo');
        $this->assertSame(0, Contenido::count());
    }

    public function test_rechaza_un_video_mp4()
    {
        $this->crear(UploadedFile::fake()->create('banner.mp4', 1000, 'video/mp4'))
            ->assertSessionHasErrors('archivo');
        $this->assertSame(0, Contenido::count());
    }

    public function test_rechaza_una_imagen_de_mas_de_2_mb()
    {
        $this->crear(UploadedFile::fake()->image('banner.jpg')->size(2049))
            ->assertSessionHasErrors('archivo');
        $this->assertSame(0, Contenido::count());
    }

    public function test_acepta_una_imagen_png_de_2_mb()
    {
        $this->crear(UploadedFile::fake()->image('banner.png')->size(2048))
            ->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists(Contenido::firstOrFail()->archivo);
    }

    public function test_al_editar_tambien_rechaza_webp()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->editorContenido()->create();
        $contenido = Contenido::create([
            'seccion' => 'banner', 'titulo' => 'Planta Coronel', 'cuerpo' => 'Texto.',
            'activo' => true, 'orden' => 0, 'id_admin' => $admin->id_admin,
        ]);

        $this->loginAdmin($admin)->put(route('admin.contenido.update', $contenido), [
            'titulo' => 'Planta Coronel', 'cuerpo' => 'Texto.',
            'archivo' => UploadedFile::fake()->image('banner.webp')->size(100),
        ])->assertSessionHasErrors('archivo');
        $this->assertNull($contenido->fresh()->archivo);
    }
}
