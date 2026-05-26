<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagen_proyecto', function (Blueprint $table) {
            $table->id('id_imagen');
            $table->binary('imagen');
            $table->string('nombre_archivo', 180);
            $table->string('tipo_mime', 80);
            $table->unsignedBigInteger('id_proyecto');

            $table->foreign('id_proyecto')
                  ->references('id_proyecto')
                  ->on('proyecto')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagen_proyecto');
    }
};
