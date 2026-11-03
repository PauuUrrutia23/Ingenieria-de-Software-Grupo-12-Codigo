<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as ConsultaTabla;
use Illuminate\Support\Facades\DB;

// Diagrama de Componentes y diagramas de secuencia (C_DBRouter): todo acceso a la Base de Datos pasa por aquí.
class DBRouterController extends Controller
{
    public function transaccion(callable $operacion): mixed
    {
        return DB::transaction($operacion);
    }

    public function tabla(string $tabla): ConsultaTabla
    {
        return DB::table($tabla);
    }

    public function query(string $modelo): Builder
    {
        return $modelo::query();
    }

    public function find(string $modelo, int|string $id): ?Model
    {
        return $modelo::find($id);
    }

    public function create(string $modelo, array $datos): Model
    {
        return $modelo::create($datos);
    }

    public function firstOrCreate(string $modelo, array $atributos, array $valores = []): Model
    {
        return $modelo::firstOrCreate($atributos, $valores);
    }

    public function update(Model $registro, array $datos): Model
    {
        $registro->update($datos);

        return $registro;
    }

    public function delete(Model $registro): bool
    {
        return (bool) $registro->delete();
    }
}
