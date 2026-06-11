<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulta', function (Blueprint $table) {
            $table->id('id_consulta');
            $table->text('mensaje');
            $table->timestamp('fecha_consulta');
            $table->string('estado', 20);
            $table->string('prioridad', 10);
            $table->unsignedBigInteger('id_visitante');
            $table->unsignedBigInteger('id_admin_responsable')->nullable();

            $table->foreign('id_visitante')
                  ->references('id_visitante')
                  ->on('visitante')
                  ->onDelete('cascade');

            $table->foreign('id_admin_responsable')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulta');
    }
};
