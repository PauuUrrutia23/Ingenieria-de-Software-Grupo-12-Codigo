<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id("id_proyecto");
            $table->string("nombre_obra", 150);
            $table->text("descripcion_tecnica");
            $table->string("region", 80);
            $table->string("ubicacion_geografica", 150);
            $table->decimal("latitud", 9, 6)->nullable();
            $table->decimal("longitud", 9, 6)->nullable();
            $table->smallInteger("anio_ejecucion");
            $table->string("estado_publicacion", 20)->default("borrador");
            $table->string("categoria", 50);
            $table->foreignId("id_admin")->constrained("administradores", "id_admin");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proyectos');
    }
};
