<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_productos_table_exists_with_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('productos'), 'No existe la tabla productos.');

        $columnas = ['id_producto', 'nombre', 'descripcion', 'imagen', 'tipo_mime', 'id_admin'];
        foreach ($columnas as $columna) {
            $this->assertTrue(
                Schema::hasColumn('productos', $columna),
                "Falta la columna productos.{$columna}."
            );
        }

        $this->assertTrue(Schema::hasColumn('productos', 'created_at'));
        $this->assertTrue(Schema::hasColumn('productos', 'updated_at'));
    }

    public function test_productos_id_admin_foreign_key_is_enforced()
    {
        $admin = \App\Models\Administrador::create([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
        ]);

        DB::table('productos')->insert([
            'nombre' => 'Cercha tipo A',
            'descripcion' => 'Cercha de pino radiata para cubiertas de gran luz.',
            'imagen' => 'productos/cercha.jpg',
            'tipo_mime' => 'image/jpeg',
            'id_admin' => $admin->id_admin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseHas('productos', ['nombre' => 'Cercha tipo A']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('productos')->insert([
            'nombre' => 'Huerfano',
            'descripcion' => 'Referencia a un admin inexistente.',
            'imagen' => 'productos/x.jpg',
            'tipo_mime' => 'image/jpeg',
            'id_admin' => 9999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_componentes_producto_table_exists_with_columns()
    {
        $this->assertTrue(Schema::hasTable('componentes_producto'));
        foreach (['id_componente', 'nombre', 'id_producto'] as $columna) {
            $this->assertTrue(
                Schema::hasColumn('componentes_producto', $columna),
                "Falta la columna componentes_producto.{$columna}."
            );
        }
    }

    public function test_invalid_producto_foreign_key_is_rejected()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('componentes_producto')->insert([
            'nombre' => 'Montante',
            'id_producto' => 9999,
        ]);
    }

    public function test_deleting_a_producto_cascades_its_componentes()
    {
        $admin = \App\Models\Administrador::create([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
        ]);

        $idProducto = DB::table('productos')->insertGetId([
            'nombre' => 'Cercha tipo B',
            'descripcion' => 'Descripción técnica.',
            'imagen' => 'productos/cercha-b.jpg',
            'tipo_mime' => 'image/jpeg',
            'id_admin' => $admin->id_admin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('componentes_producto')->insert([
            ['nombre' => 'Montante', 'id_producto' => $idProducto],
            ['nombre' => 'Solera', 'id_producto' => $idProducto],
        ]);
        $this->assertSame(2, DB::table('componentes_producto')->where('id_producto', $idProducto)->count());

        DB::table('productos')->where('id_producto', $idProducto)->delete();

        $this->assertSame(0, DB::table('componentes_producto')->where('id_producto', $idProducto)->count());
    }

    public function test_producto_relaciones_y_coleccion_de_componentes()
    {
        $admin = \App\Models\Administrador::create([
            'correo' => 'jefe@ingecon.cl',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('ClaveValida1!'),
            'rol' => 'admin_jefe',
            'activo' => true,
        ]);

        $producto = \App\Models\Producto::create([
            'nombre' => 'Cercha tipo C',
            'descripcion' => 'Cercha para cubierta.',
            'imagen' => 'productos/cercha-c.jpg',
            'tipo_mime' => 'image/jpeg',
            'id_admin' => $admin->id_admin,
        ]);

        $producto->componentes()->create(['nombre' => 'Montante']);
        $producto->componentes()->create(['nombre' => 'Solera']);

        $this->assertCount(2, $producto->fresh()->componentes);
        $this->assertSame('Cercha tipo C', $producto->componentes()->first()->producto->nombre);
        $this->assertSame($admin->id_admin, $producto->administrador->id_admin);
        $this->assertCount(1, $admin->fresh()->productos);
    }

    public function test_producto_mass_assignment_solo_permite_campos_esperados()
    {
        $producto = new \App\Models\Producto([
            'nombre' => 'Cercha',
            'descripcion' => 'Desc',
            'imagen' => 'productos/c.jpg',
            'tipo_mime' => 'image/jpeg',
            'id_admin' => 1,
            'id_producto' => 999,
            'created_at' => '2000-01-01 00:00:00',
        ]);

        $this->assertSame('Cercha', $producto->nombre);
        $this->assertNull($producto->id_producto, 'El id primario no debe ser mass-assignable.');
        $this->assertNull($producto->getAttribute('created_at'));
    }
}
