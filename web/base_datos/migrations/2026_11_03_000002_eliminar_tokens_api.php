<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// La Vista Lógica de BD no tiene tokens de API: la tabla la creaba Laravel Sanctum, que el sitio no usa.
return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('personal_access_tokens');
    }

    // Sin Sanctum no hay quien use la tabla.
    public function down()
    {
    }
};
