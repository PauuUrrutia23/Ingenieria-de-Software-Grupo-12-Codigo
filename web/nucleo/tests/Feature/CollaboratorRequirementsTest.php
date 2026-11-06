<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Colaborador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollaboratorRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private function enviar(UploadedFile $logo, string $nombre): \Illuminate\Testing\TestResponse
    {
        Storage::fake('public');
        $admin = Administrador::factory()->editorContenido()->create();

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

    public function test_nombre_vacio_es_rechazado()
    {
        $this->enviar(UploadedFile::fake()->image('logo.png'), '')
            ->assertSessionHasErrors('nombre_comercial');
    }

    public function test_nombre_de_100_caracteres_es_valido()
    {
        $nombre = 'A' . str_repeat('a', 99);
        $this->enviar(UploadedFile::fake()->image('logo.png'), $nombre)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('colaboradores', ['nombre_comercial' => $nombre]);
    }

    public function test_nombre_de_101_caracteres_es_rechazado()
    {
        $nombre = 'A' . str_repeat('a', 100);
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

    public function test_editar_sin_nuevo_logo_conserva_logo_y_guarda_el_sitio_web()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->editorContenido()->create();
        $colaborador = Colaborador::create([
            'nombre_comercial' => 'Madera Norte',
            'logotipo' => 'colaboradores/original.png',
            'tipo_mime' => 'image/png',
            'sitio_web' => 'https://anterior.example',
            'id_admin' => $admin->id_admin,
        ]);

        $this->loginAdmin($admin)->put(route('admin.colaboradores.update', $colaborador), [
            'nombre_comercial' => '  Madera Renovada  ',
            'sitio_web' => 'https://nuevo.example',
        ])->assertRedirect(route('admin.colaboradores.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('colaboradores', [
            'id_colaborador' => $colaborador->id_colaborador,
            'nombre_comercial' => 'Madera Renovada',
            'logotipo' => 'colaboradores/original.png',
            'tipo_mime' => 'image/png',
            'sitio_web' => 'https://nuevo.example',
        ]);

        $this->loginAdmin($admin)->put(route('admin.colaboradores.update', $colaborador), [
            'nombre_comercial' => 'Madera Renovada',
            'sitio_web' => '',
        ])->assertRedirect(route('admin.colaboradores.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('colaboradores', [
            'id_colaborador' => $colaborador->id_colaborador,
            'logotipo' => 'colaboradores/original.png',
            'sitio_web' => null,
        ]);
    }

    public function test_modal_editar_reabre_con_datos_y_id_tras_error_de_validacion()
    {
        $admin = Administrador::factory()->editorContenido()->create();
        $colaborador = Colaborador::create([
            'nombre_comercial' => 'Madera Norte',
            'logotipo' => 'colaboradores/original.png',
            'tipo_mime' => 'image/png',
            'sitio_web' => 'https://anterior.example',
            'id_admin' => $admin->id_admin,
        ]);

        $this->loginAdmin($admin)->from(route('admin.colaboradores.index'))
            ->put(route('admin.colaboradores.update', $colaborador), [
                '_modal' => 'editar',
                'colaborador_id' => $colaborador->id_colaborador,
                'nombre_comercial' => 'nombre invalido',
                'sitio_web' => 'https://nuevo.example',
            ])->assertSessionHasErrors('nombre_comercial');

        $this->get(route('admin.colaboradores.index'))
            ->assertOk()
            ->assertSee('editar: true', false)
            ->assertSee('nombre invalido', false)
            ->assertSee('nuevo.example', false)
            ->assertSee('colaborador_id', false);
    }

    public function test_editar_pantalla_completa_rellena_nombre_anterior_tras_error()
    {
        $admin = Administrador::factory()->editorContenido()->create();
        $colaborador = Colaborador::create([
            'nombre_comercial' => 'Madera Norte',
            'logotipo' => 'colaboradores/original.png',
            'tipo_mime' => 'image/png',
            'sitio_web' => null,
            'id_admin' => $admin->id_admin,
        ]);

        $this->loginAdmin($admin)->from(route('admin.colaboradores.edit', $colaborador))
            ->put(route('admin.colaboradores.update', $colaborador), [
                'nombre_comercial' => 'nombre invalido',
            ])->assertSessionHasErrors('nombre_comercial');

        $this->get(route('admin.colaboradores.edit', $colaborador))
            ->assertOk()
            ->assertSee('value="nombre invalido"', false);
    }
}
