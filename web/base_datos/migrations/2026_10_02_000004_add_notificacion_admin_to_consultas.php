<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 11 — RF35: estado persistente del aviso administrativo. Se añade a la
     * propia tabla de consultas en lugar de una tabla separada (decisión del plan).
     */
    public function up()
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->boolean('notificacion_admin_pendiente')->default(false);
            $table->text('notificacion_admin_ultimo_error')->nullable();
        });
    }

    public function down()
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropColumn(['notificacion_admin_pendiente', 'notificacion_admin_ultimo_error']);
        });
    }
};
