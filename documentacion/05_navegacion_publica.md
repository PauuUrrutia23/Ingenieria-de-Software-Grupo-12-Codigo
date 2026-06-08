# 05 — Navegación Pública: Layout, Navbar y Menú Lateral
## Plataforma Web Ingecon — Especificación Técnica

> **Destinatario:** Modelo de lenguaje generador de código.  
> **Propósito:** Especificación completa del sistema de navegación de la página pública: layout principal, barra de navegación fija, menú lateral deslizante, página principal con secciones y controlador.  
> **Requerimientos cubiertos:** RF12, RF13 / CU 2.2, CU 2.3.  
> **Dependencia:** El modal de login se especifica en `03_autenticacion.md`. Este documento asume que `@include('auth.login-modal')` existe y responde al evento global `abrir-login`.

---

## Estructura de Archivos a Crear

```
app/
└── Http/
    └── Controllers/
        └── InstitucionalCtrl.php

resources/views/
├── layouts/
│   └── public.blade.php
├── partials/
│   ├── navbar.blade.php
│   └── sidebar-menu.blade.php
└── public/
    ├── index.blade.php
    └── partials/
        ├── inicio.blade.php
        ├── proyectos.blade.php
        ├── certificaciones.blade.php
        └── contacto.blade.php        ← especificado en 04_formulario_contacto.md
```

---

## 1. Ruta — `routes/web.php`

Agregar al inicio del archivo, antes de las rutas de autenticación:

```php
use App\Http\Controllers\InstitucionalCtrl;

// -----------------------------------------------------------------------
// Página pública principal — sin autenticación
// -----------------------------------------------------------------------
Route::get('/', [InstitucionalCtrl::class, 'index'])->name('inicio');
```

---

## 2. Controlador — `InstitucionalCtrl`

**Archivo:** `app/Http/Controllers/InstitucionalCtrl.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InstitucionalCtrl extends Controller
{
    /**
     * Renderiza la página principal pública de Ingecon.
     * Sin datos dinámicos por ahora; los módulos de proyectos y
     * certificaciones se completarán en incrementos posteriores.
     *
     * CU 2.2 / CU 2.3 — Navegación pública (RF12, RF13)
     *
     * @return View
     */
    public function index(): View
    {
        return view('public.index');
    }
}
```

---

## 3. Layout Principal — `public.blade.php`

**Archivo:** `resources/views/layouts/public.blade.php`

```blade
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
```

---

## 4. Barra de Navegación Fija — `navbar.blade.php`

**Archivo:** `resources/views/partials/navbar.blade.php`

```blade
{{--
    Barra de Navegación Fija — Ingecon (RF13 / CU 2.3)
    Permanece visible durante el scroll vertical.
    Comunicación con sidebar-menu vía evento Alpine: 'abrir-sidebar'
    Comunicación con modal de login vía evento Alpine: 'abrir-login'
--}}

<header
    class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md h-16"
    x-data
>
    <div class="h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">

        {{-- ============================================================
             IZQUIERDA — Ícono hamburger (abre el menú lateral RF12)
             ============================================================ --}}
        <button
            type="button"
            @click="$dispatch('abrir-sidebar')"
            class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100
                   transition-colors focus:outline-none focus-visible:ring-2
                   focus-visible:ring-slate-400"
            aria-label="Abrir menú de navegación"
            aria-expanded="false"
            aria-controls="sidebar-panel"
        >
            {{-- Ícono hamburger (3 líneas) --}}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        {{-- ============================================================
             CENTRO — Logotipo / Nombre de la empresa
             ============================================================ --}}
        <div class="absolute left-1/2 -translate-x-1/2 flex items-center gap-2">
            {{-- Placeholder logotipo — reemplazar con <img> cuando exista el asset --}}
            <div class="w-8 h-8 bg-slate-900 rounded-md flex items-center justify-center shrink-0"
                 aria-hidden="true">
                <span class="text-white text-xs font-black tracking-tighter">IC</span>
            </div>
            <a
                href="/"
                class="text-slate-900 font-bold text-lg tracking-wide
                       hover:text-slate-700 transition-colors whitespace-nowrap"
                aria-label="Ingecon — Volver al inicio"
            >
                INGECON
            </a>
        </div>

        {{-- ============================================================
             DERECHA — Links de sección + ícono candado (login)
             ============================================================ --}}
        <nav
            class="flex items-center gap-1"
            aria-label="Navegación principal"
        >
            {{-- Links de sección — visibles solo en pantallas medianas+ --}}
            <ul class="hidden md:flex items-center gap-1" role="list">

                <li>
                    <button
                        type="button"
                        @click="document.getElementById('inicio').scrollIntoView({ behavior: 'smooth' })"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors"
                    >
                        Inicio
                    </button>
                </li>

                <li>
                    <button
                        type="button"
                        @click="document.getElementById('proyectos').scrollIntoView({ behavior: 'smooth' })"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors"
                    >
                        Proyectos
                    </button>
                </li>

                <li>
                    <button
                        type="button"
                        @click="document.getElementById('certificaciones').scrollIntoView({ behavior: 'smooth' })"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors"
                    >
                        Certificaciones
                    </button>
                </li>

                <li>
                    <button
                        type="button"
                        @click="document.getElementById('contacto').scrollIntoView({ behavior: 'smooth' })"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors"
                    >
                        Contacto
                    </button>
                </li>

            </ul>

            {{-- Separador visual — solo en md+ --}}
            <div class="hidden md:block w-px h-5 bg-slate-200 mx-1" aria-hidden="true"></div>

            {{-- Ícono candado — abre modal de login (RF28 en 03_autenticacion.md) --}}
            <button
                type="button"
                @click="$dispatch('abrir-login')"
                class="p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100
                       transition-colors focus:outline-none focus-visible:ring-2
                       focus-visible:ring-slate-400"
                aria-label="Acceso al panel de administración"
                title="Acceso al panel de administración"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25
                             2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25
                             2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </button>

        </nav>

    </div>
</header>
```

---

## 5. Menú Lateral Deslizante — `sidebar-menu.blade.php`

**Archivo:** `resources/views/partials/sidebar-menu.blade.php`

```blade
{{--
    Menú Lateral Deslizante — Ingecon (RF12 / CU 2.2)
    Se abre disparando el evento Alpine global 'abrir-sidebar'.
    Se cierra: botón X, click en overlay, o al seleccionar una sección.
--}}

<div
    id="sidebar-panel"
    x-data="{ abierto: false }"
    @abrir-sidebar.window="abierto = true"
    @keydown.escape.window="abierto = false"
    role="dialog"
    aria-modal="true"
    aria-label="Menú de navegación lateral"
    x-bind:aria-hidden="!abierto"
>

    {{-- ================================================================
         Overlay oscuro detrás del panel
         ================================================================ --}}
    <div
        x-show="abierto"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="abierto = false"
        class="fixed inset-0 bg-black/50 z-40 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- ================================================================
         Panel lateral deslizante
         ================================================================ --}}
    <aside
        x-show="abierto"
        x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed left-0 top-0 h-full w-64 bg-white z-50 shadow-xl
               flex flex-col overflow-y-auto"
    >

        {{-- ============================================================
             Cabecera del panel — logo + botón cerrar
             ============================================================ --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-slate-900 rounded-md flex items-center justify-center"
                     aria-hidden="true">
                    <span class="text-white text-xs font-black tracking-tighter">IC</span>
                </div>
                <span class="text-slate-900 font-bold text-base tracking-wide">
                    INGECON
                </span>
            </div>

            {{-- Botón cerrar (X) --}}
            <button
                type="button"
                @click="abierto = false"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700
                       hover:bg-slate-100 transition-colors
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                aria-label="Cerrar menú"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- ============================================================
             Etiqueta de sección de navegación
             ============================================================ --}}
        <p class="px-5 pt-5 pb-2 text-xs font-semibold text-slate-400 uppercase tracking-widest">
            Secciones
        </p>

        {{-- ============================================================
             Links de navegación (RF12 — CU 2.2)
             Cada link: cierra el panel + scroll suave a la sección
             ============================================================ --}}
        <nav aria-label="Menú lateral de secciones">
            <ul class="flex flex-col px-3 gap-0.5" role="list">

                {{-- Inicio --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('inicio').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono casa --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 12l8.954-8.955c.44-.439 1.152-.439
                                     1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504
                                     1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125
                                     1.125-1.125h2.25c.621 0 1.125.504 1.125
                                     1.125V21h4.125c.621 0 1.125-.504
                                     1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                        Inicio
                    </button>
                </li>

                {{-- Proyectos --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('proyectos').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono edificio/construcción --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9
                                     6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5
                                     3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125
                                     1.125-1.125h3.75c.621 0 1.125.504
                                     1.125 1.125V21"/>
                        </svg>
                        Proyectos
                    </button>
                </li>

                {{-- Certificaciones --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('certificaciones').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono medalla/certificado --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63
                                     2.39-1.593 3.068a3.745 3.745 0 01-1.043
                                     3.296 3.745 3.745 0 01-3.296 1.043A3.745
                                     3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746
                                     3.746 0 01-3.296-1.043 3.745 3.745 0
                                     01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39
                                     1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746
                                     0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63
                                     3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746
                                     0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                        </svg>
                        Certificaciones
                    </button>
                </li>

                {{-- Contacto --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('contacto').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono sobre/mensaje --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25
                                     2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5
                                     0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0
                                     00-2.25 2.25m19.5 0v.243a2.25 2.25 0
                                     01-1.07 1.916l-7.5 4.615a2.25 2.25 0
                                     01-2.36 0L3.32 8.91a2.25 2.25 0
                                     01-1.07-1.916V6.75"/>
                        </svg>
                        Contacto
                    </button>
                </li>

            </ul>
        </nav>

        {{-- ============================================================
             Separador y acceso rápido al panel de administración
             ============================================================ --}}
        <div class="mt-auto px-3 pb-5 pt-4 border-t border-slate-100">
            <button
                type="button"
                @click="
                    abierto = false;
                    $nextTick(() => $dispatch('abrir-login'));
                "
                class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                       text-sm font-medium text-slate-500
                       hover:bg-slate-100 hover:text-slate-900
                       transition-colors text-left"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor"
                     stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75
                             11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25
                             2.25 0 00-2.25-2.25H6.75a2.25 2.25 0
                             00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                Acceso al panel
            </button>
        </div>

    </aside>

</div>
```

> **Nota sobre `$nextTick`:** Al cerrar el panel (`abierto = false`) y luego llamar a `scrollIntoView`, se usa `$nextTick` para esperar a que Alpine procese el cierre del panel antes de iniciar el scroll. Esto evita un conflicto visual entre la animación de cierre y el desplazamiento de la página.

---

## 6. Página Principal — `index.blade.php`

**Archivo:** `resources/views/public/index.blade.php`

```blade
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
```

---

## 7. Partials de Contenido — Placeholders Iniciales

Estos archivos contienen contenido estático de placeholder. Se completarán con datos dinámicos en incrementos posteriores del proyecto.

### 7a. `inicio.blade.php`

**Archivo:** `resources/views/public/partials/inicio.blade.php`

```blade
{{--
    Sección Inicio — Hero público de Ingecon
    Contenido dinámico: pendiente de incremento de portafolio
--}}

<div class="text-center max-w-4xl mx-auto">

    <span class="inline-block px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold
                 rounded-full uppercase tracking-widest mb-6">
        Empresa Constructora Chilena
    </span>

    <h1
        id="inicio-titulo"
        class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900
               leading-tight tracking-tight mb-6"
    >
        Construimos el
        <span class="text-slate-500">Chile</span>
        de mañana
    </h1>

    <p class="text-slate-600 text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
        Más de una década de experiencia en infraestructura vial, obras civiles
        e instalaciones industriales a lo largo de todo el país.
    </p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <button
            type="button"
            @click="document.getElementById('proyectos').scrollIntoView({ behavior: 'smooth' })"
            class="px-7 py-3 bg-slate-900 hover:bg-slate-700 text-white font-semibold
                   text-sm rounded-xl transition-colors"
        >
            Ver proyectos
        </button>
        <button
            type="button"
            @click="document.getElementById('contacto').scrollIntoView({ behavior: 'smooth' })"
            class="px-7 py-3 bg-white hover:bg-slate-50 text-slate-900 font-semibold
                   text-sm rounded-xl border border-slate-200 transition-colors"
        >
            Contactar
        </button>
    </div>

</div>
```

### 7b. `proyectos.blade.php`

**Archivo:** `resources/views/public/partials/proyectos.blade.php`

```blade
{{--
    Sección Proyectos — Portafolio público
    IMPORTANTE: Este placeholder será REEMPLAZADO completamente por
    @include('public.partials.galeria') según 06_galeria_proyectos.md.
    El id="proyectos-titulo" lo define galeria.blade.php — NO agregarlo aquí
    para evitar IDs duplicados en el DOM.
--}}

<div class="text-center mb-12">
    <h2
        class="text-3xl font-bold text-slate-900 mb-3"
    >
        Nuestros Proyectos
    </h2>
    <p class="text-slate-600 text-lg max-w-xl mx-auto">
        Obras ejecutadas con estándares de calidad en todo Chile.
    </p>
</div>

{{-- Placeholder: reemplazar con grid dinámico de proyectos --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach(range(1, 3) as $i)
    <div class="bg-slate-100 rounded-2xl h-56 flex items-center justify-center">
        <p class="text-slate-400 text-sm font-medium">Proyecto {{ $i }} — próximamente</p>
    </div>
    @endforeach
</div>
```

### 7c. `certificaciones.blade.php`

**Archivo:** `resources/views/public/partials/certificaciones.blade.php`

```blade
{{--
    Sección Certificaciones — Certificados de calidad por proyecto
    Contenido dinámico: se completará con datos de la tabla CERTIFICADO
    en el incremento de gestión de certificaciones (módulo futuro).
--}}

<div class="text-center mb-12">
    <h2
        id="certificaciones-titulo"
        class="text-3xl font-bold text-slate-900 mb-3"
    >
        Certificaciones
    </h2>
    <p class="text-slate-600 text-lg max-w-xl mx-auto">
        Documentación de calidad y cumplimiento normativo de nuestras obras.
    </p>
</div>

{{-- Placeholder: reemplazar con listado dinámico de certificados --}}
<div class="max-w-2xl mx-auto space-y-4">
    @foreach(range(1, 3) as $i)
    <div class="bg-slate-100 rounded-xl h-16 flex items-center px-6">
        <p class="text-slate-400 text-sm font-medium">Certificado {{ $i }} — próximamente</p>
    </div>
    @endforeach
</div>
```

---

## 8. Diagrama de Comunicación entre Componentes

```
[Navbar]
    └─ click ícono hamburger
           → $dispatch('abrir-sidebar')          → [SidebarMenu] @abrir-sidebar.window → abierto = true
    └─ click ícono candado
           → $dispatch('abrir-login')            → [LoginModal]  @abrir-login.window   → abierto = true
    └─ click link de sección (md+)
           → scrollIntoView({ behavior:'smooth' })

[SidebarMenu]
    └─ click link de sección
           → abierto = false
           → $nextTick → scrollIntoView({ behavior:'smooth' })
    └─ click "Acceso al panel"
           → abierto = false
           → $nextTick → $dispatch('abrir-login') → [LoginModal]
    └─ click overlay / tecla Escape
           → abierto = false
```

---

## 9. Consideraciones de Implementación

### CDN vs Vite en Producción

El layout usa los CDN de Tailwind (Play CDN) y Alpine.js para agilizar el prototipado. En producción, reemplazar por los assets compilados con Vite:

```blade
{{-- Reemplazar los tags CDN por: --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Y configurar `resources/js/app.js`:

```javascript
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

Y `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### `scroll-smooth` en `<html>`

La clase `scroll-smooth` en el elemento `<html>` activa el scroll suave nativo de CSS para todos los anclas y `scrollIntoView`. Es un respaldo CSS puro en caso de que el JS de `scrollIntoView` no esté disponible.

### Compensación de la Navbar Fija (`pt-20`)

La navbar tiene altura `h-16` (64 px). El `<main>` tiene `pt-20` (80 px) para dejar espacio visual adicional. Cada `<section>` también tiene `pt-20` para que cuando el scroll apunte al ID de la sección, el título no quede oculto bajo la navbar. Esto es especialmente importante en Safari y navegadores que no aplican `scroll-margin-top`.

Como alternativa CSS más robusta, agregar a `tailwind.config.js`:

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      scrollMargin: {
        'navbar': '5rem',  // 80px = h-16 + respiro
      }
    }
  }
}
```

Y aplicar `scroll-mt-navbar` a cada `<section>` en lugar de `pt-20`.

### Accesibilidad

- El sidebar usa `role="dialog"` + `aria-modal="true"` y `aria-label` para lectores de pantalla.
- `aria-hidden="true"` en el overlay evita que los lectores lo anuncien.
- El atributo `x-bind:aria-hidden="!abierto"` en el contenedor del sidebar sincroniza el estado Alpine con el árbol de accesibilidad.
- Todos los íconos SVG tienen `aria-hidden="true"` y sus botones padre tienen `aria-label` descriptivo.
- La tecla `Escape` cierra tanto el sidebar como el modal de login mediante `@keydown.escape.window`.
