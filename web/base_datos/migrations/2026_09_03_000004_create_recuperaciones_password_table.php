<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recuperaciones_password', function (Blueprint $table) {
            $table->id("id_recuperacion");
            $table->foreignId("id_admin")->constrained("administradores", "id_admin");
            $table->string("token_hash", 255);
            $table->dateTime("expira_en");
            $table->dateTime("usado_en")->nullable();
            $table->dateTime("created_at");
        });
    }

    public function down()
    {
        Schema::dropIfExists('recuperaciones_password');
    }
};
