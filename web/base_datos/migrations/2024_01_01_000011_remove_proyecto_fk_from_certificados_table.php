<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @const string Nombre de la constraint autogenerada por Laravel */
    private const FK_CERTIFICADO_PROYECTO = 'certificado_id_proyecto_foreign';

    /**
     * Elimina la dependencia entre certificado y proyecto.
     *
     * Motivo: los certificados son documentos con valor legal expuestos
     * públicamente (RF24/RF25/RF26). El ON DELETE CASCADE hacía que al
     * eliminar un proyecto (RF51) se borraran sus certificados en cascada,
     * sin advertencia ni recuperación. Ahora los certificados son
     * totalmente independientes del proyecto.
     */
    public function up(): void
    {
        Schema::table('certificado', function (Blueprint $table) {
            $table->dropForeign(self::FK_CERTIFICADO_PROYECTO);
        });

        Schema::table('certificado', function (Blueprint $table) {
            $table->dropColumn('id_proyecto');
        });
    }

    public function down(): void
    {
        Schema::table('certificado', function (Blueprint $table) {
            $table->unsignedBigInteger('id_proyecto')->nullable()->after('estado');
        });

        Schema::table('certificado', function (Blueprint $table) {
            $table->foreign('id_proyecto')
                  ->references('id_proyecto')
                  ->on('proyecto')
                  ->onDelete('cascade');
        });
    }
};