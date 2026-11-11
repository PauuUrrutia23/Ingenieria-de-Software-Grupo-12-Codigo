<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

// Subida al hosting: en producción los enlaces usan siempre el dominio de APP_URL.
class ForzarUrlDelSitio
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production') && config('app.url')) {
            URL::forceRootUrl(rtrim(config('app.url'), '/'));
            URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https');
        }

        return $next($request);
    }
}
