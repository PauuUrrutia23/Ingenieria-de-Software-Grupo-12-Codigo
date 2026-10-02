<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 10 — RF53/CU40.2: en motores donde la tabla ya existía con datos (p. ej.
     * MySQL de un equipo), convierte la FK del responsable a ON DELETE SET NULL.
     * En SQLite no se ejecuta: la migración de creación ya lo declara y SQLite no
     * admite ALTER de claves foráneas.
     */
    public function up()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropForeign('consultas_id_admin_responsable_foreign');
            $table->foreign('id_admin_responsable')
                ->references('id_admin')
                ->on('administradores')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropForeign('consultas_id_admin_responsable_foreign');
            $table->foreign('id_admin_responsable')
                ->references('id_admin')
                ->on('administradores');
        });
    }
};
