<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Contenido;
use App\Models\Producto;
use App\Models\Proyecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Ajuste del esquema al modelo lógico aprobado del Incremento 3 (propuesta "tabla_producto"):
 * PRODUCTO con orden/activo e imagen opcional, COMPONENTE_PRODUCTO con orden,
 * PROYECTO con comuna separada de la región y la ficha de conectores en su propia sección.
 */
class ModeloIncremento3Test extends TestCase
{
    use RefreshDatabase;

    private function adminId(): int
    {
        return Administrador::create([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
        ])->id_admin;
    }

    public function test_columnas_del_modelo_logico()
    {
        foreach (['orden', 'activo'] as $columna) {
            $this->assertTrue(Schema::hasColumn('productos', $columna), "Falta productos.$columna");
        }
        $this->assertTrue(Schema::hasColumn('componentes_producto', 'orden'));
        $this->assertTrue(Schema::hasColumn('proyectos', 'comuna'));
        $this->assertFalse(Schema::hasColumn('proyectos', 'ubicacion_geografica'));
    }

    public function test_producto_sin_imagen_se_guarda_y_queda_activo()
    {
        $producto = Producto::create([
            'nombre' => 'Conector sin foto', 'descripcion' => 'Pieza de prueba.', 'id_admin' => $this->adminId(),
        ]);

        $producto->refresh();
        $this->assertNull($producto->imagen);
        $this->assertNull($producto->tipo_mime);
        $this->assertTrue($producto->activo);
        $this->assertSame(0, $producto->orden);
    }

    public function test_producto_inactivo_no_se_muestra_y_se_respeta_el_orden()
    {
        $admin = $this->adminId();
        Producto::create(['nombre' => 'Segundo', 'descripcion' => 'x', 'orden' => 2, 'id_admin' => $admin]);
        Producto::create(['nombre' => 'Primero', 'descripcion' => 'x', 'orden' => 1, 'id_admin' => $admin]);
        Producto::create(['nombre' => 'Oculto', 'descripcion' => 'x', 'orden' => 0, 'activo' => false, 'id_admin' => $admin]);

        $this->get('/')->assertStatus(200)
            ->assertViewHas('productos', fn ($productos) => $productos->pluck('nombre')->all() === ['Primero', 'Segundo'])
            ->assertDontSee('Oculto');
    }

    public function test_componentes_se_listan_segun_su_orden()
    {
        $producto = Producto::create(['nombre' => 'Cercha', 'descripcion' => 'x', 'id_admin' => $this->adminId()]);
        $producto->componentes()->create(['nombre' => 'Conector', 'orden' => 2]);
        $producto->componentes()->create(['nombre' => 'Montante', 'orden' => 1]);

        $this->assertSame(['Montante', 'Conector'], $producto->fresh()->componentes->pluck('nombre')->all());
    }

    public function test_comuna_se_busca_y_se_muestra_junto_a_la_region()
    {
        Proyecto::factory()->create([
            'nombre_obra' => 'Galpón Coronel', 'comuna' => 'Coronel', 'region' => 'Biobío',
            'categoria' => 'industrial', 'estado_publicacion' => 'publicado',
        ]);

        $this->get('/proyectos?q=Coronel')->assertStatus(200)->assertSee('Galpón Coronel')->assertSee('Coronel, Biobío');
        $this->get('/proyectos?q=Biob')->assertStatus(200)->assertSee('Galpón Coronel');
    }

    public function test_ficha_de_conectores_se_lee_desde_su_seccion_propia()
    {
        Contenido::create([
            'seccion' => 'ficha_conectores', 'titulo' => 'descripcion', 'cuerpo' => 'Texto de la ficha de conectores.',
            'orden' => 1, 'activo' => true, 'id_admin' => $this->adminId(),
        ]);

        $this->get('/producto')->assertStatus(200)
            ->assertViewHas('ficha', fn (array $ficha) => $ficha['descripcion'] === 'Texto de la ficha de conectores.');
    }
}
