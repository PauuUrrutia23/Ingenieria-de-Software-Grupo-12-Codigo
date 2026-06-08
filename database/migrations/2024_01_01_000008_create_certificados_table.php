<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificado', function (Blueprint $table) {
            $table->id('id_certificado');
            $table->string('codigo_lote', 80);
            $table->binary('archivo_pdf');
            $table->date('fecha_emision');
            $table->string('estado', 20);
            $table->unsignedBigInteger('id_proyecto');

            $table->foreign('id_proyecto')
                  ->references('id_proyecto')
                  ->on('proyecto')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificado');
    }
};
