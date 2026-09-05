<?php

namespace App\Providers;

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
        // Las migraciones viven en web/base_datos/migrations (hermano de nucleo/),
        // no en database/migrations.
        $this->loadMigrationsFrom(base_path('../base_datos/migrations'));
    }
}
