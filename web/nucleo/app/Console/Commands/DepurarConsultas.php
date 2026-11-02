<?php

namespace App\Console\Commands;

use App\Models\Consulta;
use App\Models\Visitante;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// RNF24: elimina automáticamente, sin Acción de Usuario, las Consultas que cumplieron su plazo de conservación.
class DepurarConsultas extends Command
{
    protected $signature = 'consultas:depurar';
    protected $description = 'Elimina las Consultas finalizadas hace 30 días o sin cambios de estado en 6 meses (RNF24).';

    public function handle()
    {
        $ahora = Carbon::now();
        $vencidas = Consulta::query()
            ->where(fn ($q) => $q
                // 30 días desde que pasó a "Finalizada".
                ->where(fn ($f) => $f->where('estado', 'finalizada')->where('fecha_cambio_estado', '<=', $ahora->copy()->subDays(30)))
                // 6 meses sin cambios de estado, en cualquier estado.
                ->orWhere('fecha_cambio_estado', '<=', $ahora->copy()->subMonths(6)));

        DB::transaction(function () use ($vencidas) {
            $consultas = (clone $vencidas)->lockForUpdate()->pluck('id_visitante', 'id_consulta');
            Consulta::query()->whereIn('id_consulta', $consultas->keys())->delete();
            // También los datos del Visitante (Nombre, Apellido y Email) que ya no tiene otras Consultas.
            Visitante::query()->whereIn('id_visitante', $consultas->unique()->values())->whereDoesntHave('consultas')->delete();
        });

        return Command::SUCCESS;
    }
}
