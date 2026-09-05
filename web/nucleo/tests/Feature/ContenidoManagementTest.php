<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Administrador;
use App\Models\Contenido;

class ContenidoManagementTest extends TestCase
{
    use RefreshDatabase;

    /** CU34.5 - el guest no puede acceder al Panel de gestión de contenido */
    public function test_guest_cannot_access_content_panel()
    {
        $response = $this->get('/admin/contenido');
        $response->assertRedirect('/login');
    }

    /** CU43.2 - agregar una pregunta frecuente */
    public function test_admin_can_add_faq_content()
    {
        $admin = Administrador::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/contenido', [
            'seccion' => 'faq',
            'titulo' => '¿Hacen despachos a regiones?',
            'cuerpo' => 'Sí, despachamos a nivel nacional desde nuestras 3 plantas.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contenidos', [
            'seccion' => 'faq',
            'titulo' => '¿Hacen despachos a regiones?',
        ]);
    }

    /** CU43.6 - actualizar contenido existente */
    public function test_admin_can_update_content()
    {
        $admin = Administrador::factory()->create();
        $contenido = Contenido::create([
            'seccion' => 'faq', 'titulo' => 'Pregunta original', 'cuerpo' => 'Respuesta original',
            'orden' => 0, 'activo' => true, 'id_admin' => $admin->id_admin,
        ]);

        $response = $this->actingAs($admin)->put("/admin/contenido/{$contenido->id_contenido}", [
            'titulo' => 'Pregunta editada',
            'cuerpo' => 'Respuesta editada',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contenidos', ['id_contenido' => $contenido->id_contenido, 'titulo' => 'Pregunta editada']);
    }

    /** CU44.1 - eliminar contenido con confirmación */
    public function test_admin_can_delete_content()
    {
        $admin = Administrador::factory()->create();
        $contenido = Contenido::create([
            'seccion' => 'opiniones', 'titulo' => 'Cliente feliz', 'cuerpo' => 'Excelente servicio.',
            'orden' => 0, 'activo' => true, 'id_admin' => $admin->id_admin,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/contenido/{$contenido->id_contenido}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('contenidos', ['id_contenido' => $contenido->id_contenido]);
    }
}
