{{--
    Página completa de Certificaciones — ruta GET /certificaciones
    Retornada por ProyectoController@certificaciones (RF25, RF26)

    Diferencia con public/index.blade.php:
    - index.blade.php: página principal con TODAS las secciones (scroll one-page)
    - Esta vista: página independiente enfocada solo en certificaciones
      útil para compartir el enlace directo o acceder desde buscadores.

    Variable recibida:
      $certificados  Collection<Certificado>  (eager loaded con proyecto)
--}}

@extends('layouts.public')

@section('title', 'Certificaciones Técnicas — Ingecon')

@section('content')

<main class="min-h-screen pt-24 pb-16 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-5xl mx-auto">

        {{-- Breadcrumb de navegación --}}
        <nav class="mb-8 text-sm text-slate-500" aria-label="Ruta de navegación">
            <ol class="flex items-center gap-2">
                <li>
                    <a href="/" class="hover:text-slate-700 transition-colors">Inicio</a>
                </li>
                <li aria-hidden="true">
                    <svg class="w-4 h-4 text-slate-300" xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </li>
                <li>
                    <span class="text-slate-900 font-medium" aria-current="page">Certificaciones</span>
                </li>
            </ol>
        </nav>

        {{-- Incluir el partial del listado — $certificados se hereda del scope --}}
        @include('public.partials.certificaciones')

        {{-- Enlace de regreso al inicio --}}
        <div class="mt-12 text-center">
            <a href="/#inicio"
               class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700
                      text-sm transition-colors">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al inicio
            </a>
        </div>

    </div>
</main>

@endsection
