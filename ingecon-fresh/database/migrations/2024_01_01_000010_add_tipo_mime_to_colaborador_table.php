<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colaborador', function (Blueprint $table) {
            // Nullable: colaboradores existentes sin tipo_mime usan image/png por defecto
            $table->string('tipo_mime', 80)->nullable()->after('logotipo');
        });
    }

    public function down(): void
    {
        Schema::table('colaborador', function (Blueprint $table) {
            $table->dropColumn('tipo_mime');
        });
    }
};
