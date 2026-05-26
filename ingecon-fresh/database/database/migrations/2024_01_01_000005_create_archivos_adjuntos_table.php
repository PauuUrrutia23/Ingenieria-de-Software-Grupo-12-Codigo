<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivo_adjunto', function (Blueprint $table) {
            $table->id('id_adjunto');
            $table->binary('archivo_pdf');
            $table->string('nombre_archivo', 180);
            $table->string('tipo_mime', 80);
            $table->unsignedBigInteger('id_consulta');

            $table->foreign('id_consulta')
                  ->references('id_consulta')
                  ->on('consulta')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivo_adjunto');
    }
};
