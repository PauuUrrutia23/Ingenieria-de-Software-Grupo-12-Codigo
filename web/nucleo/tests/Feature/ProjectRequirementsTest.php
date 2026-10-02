<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 13 — RNF17 aplicado al módulo de proyectos: 2 MB, sólo JPG/JPEG/PNG, máx. 15.
 */
class ProjectRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $imagenes, string $nombre = 'Obra de prueba'): array
    {
        return [
            'nombre_obra' => $nombre,
            'descripcion_tecnica' => 'Descripción técnica de prueba suficientemente larga.',
            'region' => 'Metropolitana',
            'ubicacion_geografica' => 'Santiago, Metropolitana',
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
}
