<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('administradores', function (Blueprint $table) {
            $table->id("id_admin");
            $table->string("correo", 150)->unique();
            $table->string("password_hash", 255);
            $table->string("rol", 20);
            $table->smallInteger("intentos_fallidos")->default(0);
            $table->dateTime("bloqueado_hasta")->nullable();
            $table->boolean("activo")->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('administradores');
    }
};
