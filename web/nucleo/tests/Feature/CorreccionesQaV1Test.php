<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Producto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Correcciones de la revisión QA V1.
class CorreccionesQaV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_zona_horaria_es_la_de_chile()
    {
        $this->assertSame('America/Santiago', config('app.timezone'));
    }

    public function test_terminos_muestra_fecha_fija()
    {
        $this->get('/terminos')->assertOk()->assertSee('Última actualización · 03/10/2026', false);
    }

    public function test_jpg_valido_es_aceptado_y_5mb_permitidos()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->editorContenido()->create();
        $this->loginAdmin($admin)->post('/admin/productos', [
            'nombre' => 'Conector', 'descripcion' => 'Desc',
            'imagen' => UploadedFile::fake()->image('a.jpg')->size(4500),
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('productos', ['nombre' => 'Conector']);
    }

    public function test_eliminar_producto()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->editorContenido()->create();
        $p = Producto::create(['nombre' => 'X', 'descripcion' => 'd', 'imagen' => 'productos/x.png', 'tipo_mime' => 'image/png', 'id_admin' => $admin->id_admin]);
        $this->loginAdmin($admin)->delete(route('admin.productos.destroy', $p->id_producto))->assertRedirect(route('admin.productos.index'));
        $this->assertDatabaseMissing('productos', ['id_producto' => $p->id_producto]);
    }

    public function test_formulario_edicion_producto_conserva_id_tras_error_de_validacion()
    {
        Storage::fake('public');
        $admin = Administrador::factory()->editorContenido()->create();
        $p = Producto::create(['nombre' => 'X', 'descripcion' => 'd', 'imagen' => 'productos/x.png', 'tipo_mime' => 'image/png', 'id_admin' => $admin->id_admin]);
        $this->loginAdmin($admin)->from('/admin/productos')
            ->put(route('admin.productos.update', $p->id_producto), [
                'formulario_producto' => 'editar', 'producto_id' => $p->id_producto, 'nombre' => '', 'descripcion' => 'd',
            ])->assertSessionHasErrors('nombre');
        $this->get('/admin/productos')->assertSee('name="producto_id"', false);
    }

    public function test_no_se_puede_cambiar_la_contrasena_por_la_misma()
    {
        $admin = Administrador::factory()->editorContenido()->create();
        $actual = 'Actual#2026x';
        $admin->forceFill(['password_hash' => Hash::make($actual)])->save();
        $this->loginAdmin($admin)->put(route('admin.password.update'), [
            'password_actual' => $actual, 'password' => $actual, 'password_confirmation' => $actual,
        ])->assertSessionHasErrors('password');
    }

    public function test_portada_enlaza_cada_proyecto_a_su_ficha()
    {
        $admin = Administrador::factory()->editorContenido()->create();
        $p = Proyecto::factory()->create(['estado_publicacion' => 'publicado', 'id_admin' => $admin->id_admin]);
        $this->get('/')->assertSee('/proyectos?proyecto=' . $p->id_proyecto, false);
    }
}
