{{--
    Página completa de Proyectos — ruta GET /proyectos
    Retornada por ProyectoController@galeria (RF12)

    Diferencia con public/index.blade.php:
    - index.blade.php: galería one-page que carga vía Alpine.js/AJAX
    - Esta vista: página dedicada, accesible desde el Menú Lateral, cuyo
      contenido se renderiza server-side con datos obtenidos de la Base de
      Datos en el mismo request GET /proyectos.

    Variable recibida:
      $proyectos  Collection<stdClass>  (id_proyecto, nombre_obra,
                  descripcion_tecnica, region, ubicacion_geografica,
                  anio_ejecucion, categoria, imagen_thumbnail)
--}}

@extends('layouts.public')

@section('title', 'Proyectos — Ingecon')

@section('content')

<main class="min-h-screen pt-24 pb-16 px-4 sm:px-6 lg:px-8 bg-white">
    <div class="max-w-7xl mx-auto">

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
                    <span class="text-slate-900 font-medium" aria-current="page">Proyectos</span>
                </li>
            </ol>
        </nav>

        {{-- Cabecera --}}
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-900 mb-3">Nuestros Proyectos</h1>
            <p class="text-slate-600 text-lg max-w-xl mx-auto">
                Obras ejecutadas con estándares de calidad en todo Chile.
            </p>
        </div>

        {{-- Grilla de proyectos (renderizada desde la Base de Datos) --}}
        @if ($proyectos->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                 role="list" aria-label="Listado de proyectos">

                @foreach ($proyectos as $proyecto)
                    <article
                        class="bg-white rounded-2xl shadow-md overflow-hidden
                               hover:shadow-xl transition-all duration-200 hover:-translate-y-0.5"
                        role="listitem"
                    >
                        {{-- Imagen de portada o placeholder --}}
                        <div class="w-full h-48 overflow-hidden bg-slate-100 relative">
                            @if ($proyecto->imagen_thumbnail)
                                <img src="{{ $proyecto->imagen_thumbnail }}"
                                     alt="Imagen de {{ $proyecto->nombre_obra }}"
                                     class="w-full h-full object-cover" loading="lazy">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center
                                            bg-slate-100 text-slate-300" aria-hidden="true">
                                    <svg class="w-12 h-12 mb-2" xmlns="http://www.w3.org/2000/svg"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                         stroke-width="1.25">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159
                                                 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909
                                                 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0
                                                 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                    </svg>
                                    <span class="text-xs">Sin imagen</span>
                                </div>
                            @endif

                            @if ($proyecto->anio_ejecucion)
                                <span class="absolute top-3 right-3 bg-black/60 text-white
                                             text-xs font-semibold px-2.5 py-1 rounded-full backdrop-blur-sm">
                                    {{ $proyecto->anio_ejecucion }}
                                </span>
                            @endif
                        </div>

                        {{-- Contenido --}}
                        <div class="p-5">
                            <div class="mb-3">
                                @php
                                    $colores = [
                                        'Habitacional' => 'bg-blue-100 text-blue-700',
                                        'Industrial'   => 'bg-orange-100 text-orange-700',
                                        'Agrícola'     => 'bg-green-100 text-green-700',
                                    ];
                                    $clase = $colores[$proyecto->categoria] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                <span class="inline-block text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $clase }}">
                                    {{ $proyecto->categoria ?: 'Sin categoría' }}
                                </span>
                            </div>

                            <h2 class="text-slate-900 font-bold text-base leading-snug mb-2">
                                {{ $proyecto->nombre_obra }}
                            </h2>

                            <p class="text-slate-500 text-sm leading-relaxed mb-3">
                                {{ $proyecto->descripcion_tecnica }}
                            </p>

                            @if ($proyecto->ubicacion_geografica)
                                <div class="flex items-start gap-1.5 text-slate-400">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                         stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642
                                                 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                    <span class="text-xs">{{ $proyecto->ubicacion_geografica }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach

            </div>
        @else
            {{-- Estado vacío --}}
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="w-14 h-14 text-slate-300 mb-4" xmlns="http://www.w3.org/2000/svg"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25
                             2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0
                             00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                </svg>
                <p class="text-slate-500 font-medium text-base mb-1">Aún no hay proyectos publicados</p>
                <p class="text-slate-400 text-sm max-w-xs">Vuelve pronto para ver nuestras obras.</p>
            </div>
        @endif

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
