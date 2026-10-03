<?php

namespace Tests\Feature;

use App\Models\ImagenProyecto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagenesUrlRelativaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ficha_del_proyecto_entrega_rutas_relativas_aunque_app_url_no_coincida()
    {
        // Equipo recién instalado: APP_URL quedó en http://localhost, pero el sitio se abre en 127.0.0.1:8000.
        Storage::fake('public', ['url' => 'http://localhost/storage']);
        $proyecto = Proyecto::factory()->create();
        $ruta = UploadedFile::fake()->image('obra.jpg')->store('proyectos', 'public');
        ImagenProyecto::create([
            'imagen' => $ruta, 'nombre_archivo' => 'obra.jpg', 'tipo_mime' => 'image/jpeg',
            'id_proyecto' => $proyecto->id_proyecto,
        ]);

        $this->getJson("/proyectos/{$proyecto->id_proyecto}/detalle")
            ->assertOk()
            ->assertJsonPath('imagenes.0', '/storage/' . $ruta);
    }
}
