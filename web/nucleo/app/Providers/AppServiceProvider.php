<?php

namespace App\Providers;

use App\Http\Controllers\DBRouterController;
use App\Models\Contenido;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('../base_datos/migrations'));

        // RF02 (Fase 17) y RF03 (Fase 28): el pie de página necesita, en cualquier vista
        // que monte app-layout, la URL vigente del documento de Términos y la de Ubicación.
        // Se resuelven aquí (fuera de Blade) vía DBRouter; null si no hay o falla la BD.
        $urlVigente = function (string $seccion): ?string {
            try {
                $registro = app(DBRouterController::class)
                    ->query(Contenido::class)
                    ->where('seccion', $seccion)
                    ->where('activo', true)
                    ->orderBy('id_contenido', 'desc')
                    ->first();
            } catch (\Throwable $e) {
                return null;
            }

            $url = $registro->enlace ?? null;

            return ($url && $url !== '#') ? $url : null;
        };

        View::composer('*', function ($view) use ($urlVigente) {
            if (!$view->offsetExists('terminosUrl')) {
                $view->with('terminosUrl', $urlVigente('terminos'));
            }
            if (!$view->offsetExists('ubicacionUrl')) {
                $view->with('ubicacionUrl', $urlVigente('ubicacion'));
            }
        });
    }
}

