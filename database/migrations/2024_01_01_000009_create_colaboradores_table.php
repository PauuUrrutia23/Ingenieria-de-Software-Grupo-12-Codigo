<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaborador', function (Blueprint $table) {
            $table->id('id_colaborador');
            $table->string('nombre_comercial', 120);
            $table->binary('logotipo');
            $table->string('tipo_mime_logotipo', 80)->default('image/png');
            $table->unsignedBigInteger('id_admin');

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaborador');
    }
};
