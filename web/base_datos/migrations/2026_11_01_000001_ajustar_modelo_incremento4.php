<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ajusta el esquema al modelo lógico del Incremento 4 (Vista_Logica_Incremento4).
return new class extends Migration
{
    public function up()
    {
        $driver = DB::getDriverName();

        // RF13, RF45, RF46: Sitio Web del Colaborador.
        // RF13, RF45, RF46: Sitio Web del Colaborador.
if (!Schema::hasColumn('colaboradores', 'sitio_web')) {
    Schema::table('colaboradores', function (Blueprint $table) {
        $table->string('sitio_web', 300)->nullable()->after('tipo_mime');
    });
}

        // RF39: constancia de aceptación. RNF24: fecha del último cambio de estado.
        Schema::table('consultas', function (Blueprint $table) {
            $table->boolean('acepta_terminos')->default(false)->after('prioridad');
            $table->dateTime('fecha_cambio_estado')->nullable()->after('acepta_terminos');
        });
        // El formulario exige la aceptación desde el Incremento 1: toda Consulta registrada la tiene.
        DB::table('consultas')->update(['acepta_terminos' => true]);
        DB::table('consultas')->update(['fecha_cambio_estado' => DB::raw('created_at')]);
        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE consultas MODIFY fecha_cambio_estado DATETIME NOT NULL');
        }

        // RF26: código único, Fecha de Emisión y Fecha de Vencimiento opcional.
        // La vigencia pasa a calcularse por la Fecha de Vencimiento, por lo que se elimina `estado`.
        Schema::table('certificados', function (Blueprint $table) {
            $table->string('codigo', 80)->nullable()->after('id_certificado');
            $table->date('fecha_emision')->nullable()->after('archivo_pdf');
            $table->date('fecha_vencimiento')->nullable()->after('fecha_emision');
        });
        $ayer = Carbon::yesterday()->toDateString();
        foreach (DB::table('certificados')->select('id_certificado', 'estado', 'created_at')->get() as $c) {
            DB::table('certificados')->where('id_certificado', $c->id_certificado)->update([
                'codigo' => 'CERT-' . str_pad((string) $c->id_certificado, 4, '0', STR_PAD_LEFT),
                'fecha_emision' => Carbon::parse($c->created_at ?? 'now')->toDateString(),
                // Un certificado vencido o revocado deja de estar vigente desde ayer.
                'fecha_vencimiento' => $c->estado === 'vigente' ? null : $ayer,
            ]);
        }
        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE certificados MODIFY codigo VARCHAR(80) NOT NULL, MODIFY fecha_emision DATE NOT NULL');
        }
        Schema::table('certificados', function (Blueprint $table) {
            $table->unique('codigo');
        });
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        // RF59: permisos por cuenta. Todas las cuentas parten sin permisos.
        Schema::table('administradores', function (Blueprint $table) {
            $table->boolean('perm_editar_contenido')->default(false)->after('activo');
            $table->boolean('perm_exportar_consultas')->default(false)->after('perm_editar_contenido');
            $table->boolean('perm_eliminar_consultas')->default(false)->after('perm_exportar_consultas');
        });

        // RF15: Historial de Cambios.
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id('id_historial');
            $table->dateTime('fecha_hora');
            $table->foreignId('id_admin')->nullable()->constrained('administradores', 'id_admin')->nullOnDelete();
            $table->string('correo_responsable', 150);
            $table->string('accion', 30);
            $table->string('modulo', 40);
            $table->text('valores_anteriores')->nullable();
            $table->text('valores_nuevos')->nullable();
            $table->unsignedBigInteger('id_registro_afectado')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('historial_cambios');

        Schema::table('administradores', function (Blueprint $table) {
            $table->dropColumn(['perm_editar_contenido', 'perm_exportar_consultas', 'perm_eliminar_consultas']);
        });

        Schema::table('certificados', function (Blueprint $table) {
            $table->string('estado', 20)->default('vigente');
        });
        $hoy = Carbon::today()->toDateString();
        DB::table('certificados')->whereNotNull('fecha_vencimiento')->where('fecha_vencimiento', '<', $hoy)
            ->update(['estado' => 'vencido']);
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
        });
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'fecha_emision', 'fecha_vencimiento']);
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropColumn(['acepta_terminos', 'fecha_cambio_estado']);
        });

        Schema::table('colaboradores', function (Blueprint $table) {
            $table->dropColumn('sitio_web');
        });
    }
};
