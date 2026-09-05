<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sesiones', function (Blueprint $table) {
            $table->id("id_sesion");
            $table->string("token_hash", 255);
            $table->dateTime("fecha_inicio");
            $table->string("estado", 20);
            $table->foreignId("id_admin")->constrained("administradores", "id_admin");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sesiones');
    }
};
