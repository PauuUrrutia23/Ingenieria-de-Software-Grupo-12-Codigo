<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DBRouterController extends Controller
{
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
