<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto', function (Blueprint $table) {
            $table->id('id_proyecto');
            $table->string('nombre_obra', 150);
            $table->text('descripcion_tecnica')->nullable();
            $table->string('region', 80)->nullable();
            $table->string('ubicacion_geografica', 150)->nullable();
            $table->smallInteger('anio_ejecucion')->nullable();
            $table->string('estado_publicacion', 20)->default('borrador');
            $table->string('categoria', 50)->nullable();
            $table->unsignedBigInteger('id_admin');

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto');
    }
};
