<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ajusta el esquema al modelo lógico del Incremento 3 (propuesta tabla_producto).
return new class extends Migration
{
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->reconstruirProductosSqlite(true);
        } else {
            DB::statement('ALTER TABLE productos MODIFY imagen VARCHAR(255) NULL, MODIFY tipo_mime VARCHAR(80) NULL');
            Schema::table('productos', function (Blueprint $table) {
                $table->smallInteger('orden')->default(0)->after('tipo_mime');
                $table->boolean('activo')->default(true)->after('orden');
            });
        }

        $largos = DB::table('componentes_producto')->whereRaw('LENGTH(nombre) > 120')->count();
        if ($largos > 0) {
            throw new RuntimeException("Hay $largos componentes con más de 120 caracteres; acórtelos antes de migrar.");
        }
        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE componentes_producto MODIFY nombre VARCHAR(120) NOT NULL');
        }
        Schema::table('componentes_producto', function (Blueprint $table) {
            $table->smallInteger('orden')->default(0);
        });

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE proyectos RENAME COLUMN ubicacion_geografica TO comuna');
        } else {
            DB::statement('ALTER TABLE proyectos CHANGE ubicacion_geografica comuna VARCHAR(150) NOT NULL');
        }
        // "Melipilla, Metropolitana" -> "Melipilla"
        foreach (DB::table('proyectos')->select('id_proyecto', 'comuna', 'region')->get() as $p) {
            $partes = array_map('trim', explode(',', (string) $p->comuna));
            if (count($partes) > 1 && mb_strtolower(end($partes)) === mb_strtolower(trim((string) $p->region))) {
                array_pop($partes);
                DB::table('proyectos')->where('id_proyecto', $p->id_proyecto)
                    ->update(['comuna' => implode(', ', $partes)]);
            }
        }

        DB::table('contenidos')->where('seccion', 'producto')->update(['seccion' => 'ficha_conectores']);
    }

    public function down()
    {
        $driver = DB::getDriverName();

        DB::table('contenidos')->where('seccion', 'ficha_conectores')->update(['seccion' => 'producto']);

        foreach (DB::table('proyectos')->select('id_proyecto', 'comuna', 'region')->get() as $p) {
            DB::table('proyectos')->where('id_proyecto', $p->id_proyecto)
                ->update(['comuna' => trim($p->comuna . ', ' . $p->region, ', ')]);
        }
        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE proyectos RENAME COLUMN comuna TO ubicacion_geografica');
        } else {
            DB::statement('ALTER TABLE proyectos CHANGE comuna ubicacion_geografica VARCHAR(150) NOT NULL');
        }

        Schema::table('componentes_producto', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE componentes_producto MODIFY nombre VARCHAR(150) NOT NULL');
        }

        if ($driver === 'sqlite') {
            $this->reconstruirProductosSqlite(false);
        } else {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn(['orden', 'activo']);
            });
            DB::table('productos')->whereNull('imagen')->update(['imagen' => '']);
            DB::table('productos')->whereNull('tipo_mime')->update(['tipo_mime' => '']);
            DB::statement('ALTER TABLE productos MODIFY imagen VARCHAR(255) NOT NULL, MODIFY tipo_mime VARCHAR(80) NOT NULL');
        }
    }

    // SQLite no permite alterar columnas: se recrea la tabla conservando los datos y la FK.
    private function reconstruirProductosSqlite(bool $ajustada): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('productos_tmp', function (Blueprint $table) use ($ajustada) {
            $table->id('id_producto');
            $table->string('nombre', 150);
            $table->text('descripcion');
            $ajustada ? $table->string('imagen', 255)->nullable() : $table->string('imagen', 255);
            $ajustada ? $table->string('tipo_mime', 80)->nullable() : $table->string('tipo_mime', 80);
            if ($ajustada) {
                $table->smallInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
            }
            $table->foreignId('id_admin')->constrained('administradores', 'id_admin');
            $table->timestamps();
        });
        $cols = 'id_producto, nombre, descripcion, imagen, tipo_mime, id_admin, created_at, updated_at';
        $origen = $ajustada ? $cols
            : "id_producto, nombre, descripcion, COALESCE(imagen, ''), COALESCE(tipo_mime, ''), id_admin, created_at, updated_at";
        DB::statement("INSERT INTO productos_tmp ($cols) SELECT $origen FROM productos");
        Schema::drop('productos');
        Schema::rename('productos_tmp', 'productos');
        Schema::enableForeignKeyConstraints();
    }
};
