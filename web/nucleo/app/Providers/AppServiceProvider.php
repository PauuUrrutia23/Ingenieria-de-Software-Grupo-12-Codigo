<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Las vistas y la BD viven fuera de nucleo/, en web/vista y web/base_datos.
        View::getFinder()->setPaths([dirname(base_path()).'/vista']);

        $this->loadMigrationsFrom(dirname(base_path()).'/base_datos/migrations');
    }
}
