<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesion', function (Blueprint $table) {
            $table->id('id_sesion');
            $table->string('token_hash', 255)->unique();
            $table->timestamp('fecha_inicio');
            $table->string('estado', 20);
            $table->unsignedBigInteger('id_admin');

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('administrador')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesion');
    }
};
