<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Vivienda industrializada' => 'construccion',
            'Construcción / Vivienda industrializada' => 'construccion',
            'Pabellones y galpones' => 'industrial',
            'Industrial / Pabellones y galpones' => 'industrial',
            'Terminaciones y servicios' => 'terminaciones',
        ] as $anterior => $canonico) {
            DB::table('proyectos')->where('categoria', $anterior)->update(['categoria' => $canonico]);
        }
    }

    public function down(): void
    {
        // La normalización es irreversible: varias etiquetas anteriores comparten un valor.
    }
};
