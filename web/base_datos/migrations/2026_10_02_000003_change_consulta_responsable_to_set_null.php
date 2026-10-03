<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Solo MySQL: en SQLite la migración de creación ya define ON DELETE SET NULL.
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
