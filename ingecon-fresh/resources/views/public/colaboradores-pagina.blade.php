{{--
    Página completa de Colaboradores — ruta GET /colaboradores
    Retornada por InstitucionalCtrl@colaboradores (RF12)

    Página dedicada accesible desde el Menú Lateral. El contenido (logotipos y
    nombres comerciales) se renderiza server-side con datos obtenidos de la
    Base de Datos en el mismo request GET /colaboradores.

    Variable recibida:
      $colaboradores  Collection<Colaborador>
--}}

@extends('layouts.public')

@section('title', 'Colaboradores — Ingecon')

@section('content')

<main class="min-h-screen pt-24 pb-16 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-6xl mx-auto">

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
                    <span class="text-slate-900 font-medium" aria-current="page">Colaboradores</span>
                </li>
            </ol>
        </nav>

        {{-- Cabecera --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-900 mb-3">Nuestros Colaboradores</h1>
            <p class="text-slate-600 text-lg max-w-xl mx-auto">
                Empresas que confían y trabajan junto a Ingecon.
            </p>
        </div>

        {{-- Grilla de colaboradores (renderizada desde la Base de Datos) --}}
        @include('public.partials.colaboradores')

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
