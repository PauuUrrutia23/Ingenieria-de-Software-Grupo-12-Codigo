@extends('layouts.public')

@section('title', 'Página no encontrada — Ingecon')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">

        <p class="text-8xl font-black text-slate-200 leading-none mb-6"
           aria-hidden="true">404</p>

        <div class="flex justify-center mb-6" aria-hidden="true">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125
                             1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0
                             12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125
                             1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0
                             1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 mb-3">
            Recurso no encontrado
        </h1>

        <p class="text-slate-500 text-base mb-8 leading-relaxed">
            {{--
                En producción (APP_ENV=production) mostrar mensaje genérico.
                En desarrollo mostrar el mensaje real del abort() para facilitar debug.
                Evita exponer mensajes internos de Laravel a usuarios finales.
            --}}
            @if(app()->isProduction())
                La página o archivo que buscas no existe o ya no está disponible.
            @else
                {{ $exception->getMessage() ?: 'La página o archivo que buscas no existe o ya no está disponible.' }}
            @endif
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/"
               class="px-6 py-2.5 bg-slate-900 hover:bg-slate-700 text-white
                      font-semibold text-sm rounded-xl transition-colors">
                Volver al inicio
            </a>
            <a href="javascript:history.back()"
               class="px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-700
                      font-semibold text-sm rounded-xl border border-slate-200
                      transition-colors">
                Página anterior
            </a>
        </div>

    </div>
</div>
@endsection
