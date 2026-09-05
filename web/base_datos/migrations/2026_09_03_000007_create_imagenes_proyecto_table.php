<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('imagenes_proyecto', function (Blueprint $table) {
            $table->id("id_imagen");
            $table->string("imagen", 255);
            $table->string("nombre_archivo", 180);
            $table->string("tipo_mime", 80);
            $table->foreignId("id_proyecto")->constrained("proyectos", "id_proyecto")->onDelete("cascade");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('imagenes_proyecto');
    }
};
