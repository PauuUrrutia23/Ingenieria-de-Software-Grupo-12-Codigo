<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('colaboradores', function (Blueprint $table) {
            $table->id("id_colaborador");
            $table->string("nombre_comercial", 120);
            $table->string("logotipo", 255);
            $table->string("tipo_mime", 80)->nullable();
            $table->foreignId("id_admin")->constrained("administradores", "id_admin");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('colaboradores');
    }
};
