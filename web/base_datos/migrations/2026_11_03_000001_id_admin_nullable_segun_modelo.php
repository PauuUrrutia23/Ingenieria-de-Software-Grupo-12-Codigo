<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Vista Lógica de BD: id_admin es "FK NULL" en COLABORADOR, CONTENIDO, PROYECTO y PRODUCTO,
// igual que en CERTIFICADO. Al eliminar una cuenta, su contenido se sigue reasignando al jefe.
return new class extends Migration
{
    private const TABLAS = ['colaboradores', 'contenidos', 'proyectos', 'productos'];

    public function up()
    {
        $this->cambiarNulo(true);
    }

    public function down()
    {
        $this->cambiarNulo(false);
    }

    private function cambiarNulo(bool $nulo)
    {
        if (DB::getDriverName() !== 'sqlite') {
            foreach (self::TABLAS as $tabla) {
                DB::statement("ALTER TABLE $tabla MODIFY id_admin BIGINT UNSIGNED " . ($nulo ? 'NULL' : 'NOT NULL'));
            }
            return;
        }

        // SQLite no modifica columnas: se reconstruye la tabla (procedimiento de la documentación de SQLite).
        DB::statement('PRAGMA defer_foreign_keys = ON');
        foreach (self::TABLAS as $tabla) {
            [$de, $a] = $nulo ? ['"id_admin" integer not null', '"id_admin" integer'] : ['"id_admin" integer,', '"id_admin" integer not null,'];
            $sql = DB::selectOne("select sql from sqlite_master where type = 'table' and name = ?", [$tabla])->sql;
            $indices = array_column(DB::select("select sql from sqlite_master where type = 'index' and tbl_name = ? and sql is not null", [$tabla]), 'sql');

            DB::statement(str_replace(["CREATE TABLE \"$tabla\"", $de], ["CREATE TABLE \"{$tabla}_tmp\"", $a], $sql));
            DB::statement("INSERT INTO \"{$tabla}_tmp\" SELECT * FROM \"$tabla\"");
            DB::statement("DROP TABLE \"$tabla\"");
            DB::statement("ALTER TABLE \"{$tabla}_tmp\" RENAME TO \"$tabla\"");
            foreach ($indices as $indice) {
                DB::statement($indice);
            }
        }
    }
};
