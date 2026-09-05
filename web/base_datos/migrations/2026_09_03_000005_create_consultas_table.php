<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id("id_consulta");
            $table->text("mensaje");
            $table->date("fecha_consulta")->nullable();
            $table->string("estado", 20)->default("pendiente");
            $table->string("prioridad", 10)->nullable();
            $table->foreignId("id_visitante")->constrained("visitantes", "id_visitante");
            $table->foreignId("id_admin_responsable")->nullable()->constrained("administradores", "id_admin");
            $table->dateTime("created_at");
        });
    }

    public function down()
    {
        Schema::dropIfExists('consultas');
    }
};
