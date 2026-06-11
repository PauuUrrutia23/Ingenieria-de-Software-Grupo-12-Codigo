@extends('layouts.public')

@section('title', 'Ingecon — Empresa Constructora Chilena')

@section('meta-description', 'Ingecon: empresa de construcción chilena especializada en infraestructura vial, obras civiles y proyectos industriales. Consulta nuestro portafolio y certificaciones.')

@section('content')

    {{-- ================================================================
         SECCIÓN: Inicio (#inicio)
         ================================================================ --}}
    <section
        id="inicio"
        class="min-h-screen pt-20 flex items-center justify-center bg-slate-50"
        aria-labelledby="inicio-titulo"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-16">
            @include('public.partials.inicio')
        </div>
    </section>

    {{-- ================================================================
         SECCIÓN: Proyectos (#proyectos)
         ================================================================ --}}
    <section
        id="proyectos"
        class="min-h-screen pt-20 bg-white"
        aria-labelledby="proyectos-titulo"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-16">
            @include('public.partials.proyectos')
        </div>
    </section>

    {{-- ================================================================
         SECCIÓN: Certificaciones (#certificaciones)
         ================================================================ --}}
    <section
        id="certificaciones"
        class="min-h-screen pt-20 bg-slate-50"
        aria-labelledby="certificaciones-titulo"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-16">
            @include('public.partials.certificaciones')
        </div>
    </section>

    {{-- ================================================================
         SECCIÓN: Colaboradores (#colaboradores)
         ================================================================ --}}
    <section
        id="colaboradores"
        class="min-h-screen pt-20 bg-white"
        aria-labelledby="colaboradores-titulo"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-16">
            <div class="text-center mb-10">
                <h2
                    id="colaboradores-titulo"
                    class="text-3xl font-bold text-slate-900 mb-3"
                >
                    Nuestros Colaboradores
                </h2>
                <p class="text-slate-600 text-lg max-w-xl mx-auto">
                    Empresas que confían y trabajan junto a Ingecon.
                </p>
            </div>
            @include('public.partials.colaboradores')
        </div>
    </section>

    {{-- ================================================================
         SECCIÓN: Contacto (#contacto)
         Partial completo especificado en 04_formulario_contacto.md
         ================================================================ --}}
    <section
        id="contacto"
        class="min-h-screen pt-20 bg-white"
        aria-labelledby="contacto-titulo"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-16">

            <div class="max-w-2xl mx-auto mb-12 text-center">
                <h2
                    id="contacto-titulo"
                    class="text-3xl font-bold text-slate-900 mb-3"
                >
                    Contáctanos
                </h2>
                <p class="text-slate-600 text-lg">
                    ¿Tienes un proyecto en mente? Escríbenos y te responderemos a la brevedad.
                </p>
            </div>

            <div class="max-w-2xl mx-auto">
                @include('public.partials.contacto')
            </div>

        </div>
    </section>

@endsection
