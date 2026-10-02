<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id("id_producto");
            $table->string("nombre", 150);
            $table->text("descripcion");
            $table->string("imagen", 255);
            $table->string("tipo_mime", 80);
            $table->foreignId("id_admin")->constrained("administradores", "id_admin");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('productos');
    }
};
