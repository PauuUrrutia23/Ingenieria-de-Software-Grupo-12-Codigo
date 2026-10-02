<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('componentes_producto', function (Blueprint $table) {
            $table->id("id_componente");
            $table->string("nombre", 150);
            $table->foreignId("id_producto")->constrained("productos", "id_producto")->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('componentes_producto');
    }
};
