<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Administrador;
use App\Models\Visitante;
use App\Models\Consulta;
use App\Models\Proyecto;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_rejects_message_shorter_than_10_characters()
    {
        $response = $this->post('/contacto', [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'email' => 'juan@example.com',
            'mensaje' => 'Hola',
            'acepta_terminos' => 'on',
        ]);

        $response->assertSessionHasErrors('mensaje');
        $this->assertDatabaseMissing('consultas', ['mensaje' => 'Hola']);
    }

    public function test_contact_form_blocks_after_5_pending_consultas_in_24h()
    {
        $visitante = Visitante::factory()->create(['email' => 'repetido@example.com']);
        Consulta::factory()->count(5)->create([
            'id_visitante' => $visitante->id_visitante,
            'estado' => 'pendiente',
            'created_at' => now(),
        ]);

        $response = $this->post('/contacto', [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'email' => 'repetido@example.com',
            'mensaje' => 'Esta es la sexta consulta que intento enviar hoy mismo.',
            'acepta_terminos' => 'on',
        ]);

        $response->assertSessionHasErrors('mensaje');
        $this->assertEquals(5, Consulta::where('id_visitante', $visitante->id_visitante)->count());
    }

    public function test_draft_project_is_hidden_from_public_gallery()
    {
        $admin = Administrador::factory()->create();
        Proyecto::factory()->create(['nombre_obra' => 'Obra Publicada Visible', 'estado_publicacion' => 'publicado', 'id_admin' => $admin->id_admin]);
        Proyecto::factory()->create(['nombre_obra' => 'Obra en Borrador Oculta', 'estado_publicacion' => 'borrador', 'id_admin' => $admin->id_admin]);

        $response = $this->get('/proyectos');

        $response->assertStatus(200);
        $response->assertSee('Obra Publicada Visible');
        $response->assertDontSee('Obra en Borrador Oculta');
    }

    public function test_consultas_are_paginated_in_blocks_of_10()
    {
        $admin = Administrador::factory()->create();
        Consulta::factory()->count(15)->create();

        $response = $this->actingAs($admin)->get('/admin/consultas');

        $response->assertStatus(200);
        $response->assertViewHas('consultas', function ($consultas) {
            return $consultas->count() === 10 && $consultas->total() === 15;
        });
    }

    public function test_colaborador_logo_over_500kb_is_rejected()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $logoGrande = UploadedFile::fake()->image('logo.png')->size(600);

        $response = $this->actingAs($admin)->post('/admin/colaboradores', [
            'nombre_comercial' => 'Proveedor Test',
            'logotipo' => $logoGrande,
        ]);

        $response->assertSessionHasErrors('logotipo');
        $this->assertDatabaseMissing('colaboradores', ['nombre_comercial' => 'Proveedor Test']);
    }

    public function test_proyecto_rejects_more_than_15_images()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->create();
        $imagenes = [];
        for ($i = 0; $i < 16; $i++) {
            $imagenes[] = UploadedFile::fake()->image("foto{$i}.jpg");
        }

        $response = $this->actingAs($admin)->post('/admin/proyectos', [
            'nombre_obra' => 'Proyecto con demasiadas fotos',
            'descripcion_tecnica' => 'Descripción técnica de prueba suficientemente larga.',
            'region' => 'Metropolitana',
            'ubicacion_geografica' => 'Santiago, Metropolitana',
            'anio_ejecucion' => 2025,
            'categoria' => 'construccion',
            'estado_publicacion' => 'borrador',
            'imagenes' => $imagenes,
        ]);

        $response->assertSessionHasErrors('imagenes');
        $this->assertDatabaseMissing('proyectos', ['nombre_obra' => 'Proyecto con demasiadas fotos']);
    }
}
