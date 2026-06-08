<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitante', function (Blueprint $table) {
            $table->id('id_visitante');
            $table->string('nombre', 80);
            $table->string('apellido', 80);
            $table->string('email', 150)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitante');
    }
};
