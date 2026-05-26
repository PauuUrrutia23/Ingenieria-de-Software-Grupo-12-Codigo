<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta-description', 'Ingecon — Empresa de construcción chilena. Proyectos de infraestructura, obras civiles y certificaciones de calidad.')">

    <title>@yield('title', 'Ingecon — Empresa Constructora')</title>

    {{-- ----------------------------------------------------------------
         Tailwind CSS v3 — CDN (Play CDN para prototipado/desarrollo)
         Para producción: reemplazar por build compilado con Vite
         ---------------------------------------------------------------- --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- ----------------------------------------------------------------
         Alpine.js v3 — CDN
         Debe cargarse en el <head> con defer para que x-cloak funcione
         correctamente antes del primer render.
         ---------------------------------------------------------------- --}}
    <script
        defer
        src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"
    ></script>
    {{-- NOTA: versión fijada a 3.14.1 — no usar @3.x.x porque jsDelivr
         no siempre resuelve a la última estable y puede servir versiones
         distintas entre deploys causando comportamiento inconsistente. --}}

    {{-- Ocultar elementos Alpine antes de que el framework inicialice --}}
    <style>
        [x-cloak] { display: none !important; }
    </style>

    {{-- Stack para estilos adicionales por vista --}}
    @stack('styles')
</head>

<body class="bg-white text-slate-800 antialiased">

    {{-- ----------------------------------------------------------------
         Barra de navegación fija (RF13 — CU 2.3)
         Se incluye fuera del contenido principal para que quede
         posicionada sobre cualquier sección.
         ---------------------------------------------------------------- --}}
    @include('partials.navbar')

    {{-- ----------------------------------------------------------------
         Menú lateral deslizante (RF12 — CU 2.2)
         Se incluye fuera del flujo principal para el z-index correcto.
         ---------------------------------------------------------------- --}}
    @include('partials.sidebar-menu')

    {{-- ----------------------------------------------------------------
         Modal de login (especificado en 03_autenticacion.md)
         Responde al evento global 'abrir-login'.
         ---------------------------------------------------------------- --}}
    @include('auth.login-modal')

    {{-- ----------------------------------------------------------------
         Contenido principal de cada vista
         pt-20 compensa la altura de la navbar fija (h-16 = 64px → pt-16,
         se usa pt-20 para añadir respiro visual adicional).
         ---------------------------------------------------------------- --}}
    <main class="pt-20">
        @yield('content')
    </main>

    {{-- Stack para scripts adicionales por vista --}}
    @stack('scripts')

</body>
</html>
