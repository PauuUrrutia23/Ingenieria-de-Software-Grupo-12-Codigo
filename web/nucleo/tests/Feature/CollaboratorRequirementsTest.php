<?php

namespace Tests\Feature;

use App\Models\Administrador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FASE 14 — RNF17 aplicado a colaboradores: sólo JPG/JPEG/PNG, ≤500 KB.
 */
class CollaboratorRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function enviar(UploadedFile $logo, string $nombre): \Illuminate\Testing\TestResponse
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();

        return $this->loginAdmin($admin)->post('/admin/colaboradores', [
            'nombre_comercial' => $nombre,
            'logotipo' => $logo,
        ]);
    }

    public function test_png_de_499kb_es_valido()
    {
        $this->enviar(UploadedFile::fake()->image('logo.png')->size(499), 'Proveedor PNG')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('colaboradores', ['nombre_comercial' => 'Proveedor PNG']);
    }

    public function test_logo_mayor_a_500kb_es_rechazado()
    {
        $this->enviar(UploadedFile::fake()->image('logo.png')->size(600), 'Proveedor Grande')
            ->assertSessionHasErrors('logotipo');
        $this->assertDatabaseMissing('colaboradores', ['nombre_comercial' => 'Proveedor Grande']);
    }

    public function test_pdf_es_rechazado()
    {
        $this->enviar(UploadedFile::fake()->createWithContent('logo.pdf', '%PDF-1.4 x'), 'Proveedor PDF')
            ->assertSessionHasErrors('logotipo');
        $this->assertDatabaseMissing('colaboradores', ['nombre_comercial' => 'Proveedor PDF']);
    }

    public function test_webp_es_rechazado()
    {
        $this->enviar(UploadedFile::fake()->createWithContent('logo.webp', 'contenido-falso'), 'Proveedor WebP')
            ->assertSessionHasErrors('logotipo');
        $this->assertDatabaseMissing('colaboradores', ['nombre_comercial' => 'Proveedor WebP']);
    }

    // --- FASE 15: Nombre Comercial según DS-67 (trim, max 100, primera letra mayúscula) ---

    public function test_nombre_vacio_es_rechazado()
    {
        $this->enviar(UploadedFile::fake()->image('logo.png'), '')
            ->assertSessionHasErrors('nombre_comercial');
    }

    public function test_nombre_de_100_caracteres_es_valido()
    {
        $nombre = 'A' . str_repeat('a', 99); // 100 caracteres
        $this->enviar(UploadedFile::fake()->image('logo.png'), $nombre)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('colaboradores', ['nombre_comercial' => $nombre]);
    }

    public function test_nombre_de_101_caracteres_es_rechazado()
    {
        $nombre = 'A' . str_repeat('a', 100); // 101 caracteres
        $this->enviar(UploadedFile::fake()->image('logo.png'), $nombre)
            ->assertSessionHasErrors('nombre_comercial');
    }

    public function test_primera_letra_minuscula_es_rechazada()
    {
        $this->enviar(UploadedFile::fake()->image('logo.png'), 'proveedor')
            ->assertSessionHasErrors('nombre_comercial');
    }

    public function test_nombre_valido_se_guarda_sin_espacios_de_borde()
    {
        $this->enviar(UploadedFile::fake()->image('logo.png'), '  Madera Norte  ')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('colaboradores', ['nombre_comercial' => 'Madera Norte']);
        $this->assertDatabaseMissing('colaboradores', ['nombre_comercial' => '  Madera Norte  ']);
    }
}
