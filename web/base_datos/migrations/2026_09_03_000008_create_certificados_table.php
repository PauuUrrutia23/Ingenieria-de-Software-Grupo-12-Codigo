<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id("id_certificado");
            $table->string("codigo", 80)->unique();
            $table->string("nombre", 200);
            $table->text("descripcion")->nullable();
            $table->string("imagen", 255)->nullable();
            $table->string("tipo_mime", 80)->nullable();
            $table->string("archivo_pdf", 255)->nullable();
            $table->date("fecha_emision");
            $table->string("estado", 20)->default("vigente");
            $table->string("organismo", 120);
            $table->string("url_organismo", 300)->nullable();
            $table->foreignId("id_admin")->nullable()->constrained("administradores", "id_admin");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificados');
    }
};
