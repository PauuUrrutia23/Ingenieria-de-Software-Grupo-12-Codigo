<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    // CU 34.2–34.6, 36.1, 45.2, 48.3 y 52.2: si falla la consulta de un listado del panel,
    // el módulo se muestra vacío con un aviso (lo dibuja el layout) en vez de un error 500.
    protected function cargarListado(callable $consulta, mixed $siFalla): mixed
    {
        try {
            return $consulta();
        } catch (\Throwable $e) {
            report($e);
            session()->now('listado_no_disponible', true);
            return $siFalla;
        }
    }

    protected function paginaVacia(int $porPagina): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $porPagina, 1, ['path' => request()->url()]);
    }
}
