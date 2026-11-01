<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// RF43: el equipo eliminó las Opiniones de Clientes (21-10-2026). Se borran los testimonios guardados
// porque contienen nombres de personas que ya no se usan (minimización, Ley 21.719).
return new class extends Migration
{
    public function up()
    {
        DB::table('contenidos')->where('seccion', 'opiniones')->delete();
    }

    // Los testimonios borrados no se pueden recuperar.
    public function down()
    {
    }
};
