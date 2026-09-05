<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contenidos', function (Blueprint $table) {
            $table->id("id_contenido");
            $table->string("seccion", 30);
            $table->string("titulo", 200)->nullable();
            $table->text("cuerpo")->nullable();
            $table->string("archivo", 255)->nullable();
            $table->string("tipo_mime", 80)->nullable();
            $table->string("enlace", 300)->nullable();
            $table->smallInteger("orden")->default(0);
            $table->boolean("activo")->default(true);
            $table->foreignId("id_admin")->constrained("administradores", "id_admin");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contenidos');
    }
};
