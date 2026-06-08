<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel de Gestión — Ingecon')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>

    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-800">

    <div class="min-h-full flex flex-col">

        {{-- Barra superior del panel --}}
        <header class="bg-slate-900 h-14 flex items-center px-6 gap-4 shadow-lg">
            <a href="{{ route('admin.dashboard') }}"
               class="text-white font-bold text-lg tracking-wide">
                INGECON <span class="text-slate-400 font-normal text-sm ml-2">Panel Admin</span>
            </a>
            <nav class="hidden sm:flex items-center gap-1 ml-4" aria-label="Navegacion del panel">
                <a href="{{ route('admin.dashboard') }}"
                   class="px-3 py-1 text-sm {{ request()->routeIs('admin.dashboard') ? 'text-white bg-slate-700' : 'text-slate-400 hover:text-white' }} rounded-lg transition-colors">
                    Dashboard
                </a>
                <a href="{{ route('admin.proyectos.panel') }}"
                   class="px-3 py-1 text-sm {{ request()->routeIs('admin.proyectos.*') ? 'text-white bg-slate-700' : 'text-slate-400 hover:text-white' }} rounded-lg transition-colors">
                    Proyectos
                </a>
                <a href="{{ route('admin.colaboradores.panel') }}"
                   class="px-3 py-1 text-sm {{ request()->routeIs('admin.colaboradores.*') ? 'text-white bg-slate-700' : 'text-slate-400 hover:text-white' }} rounded-lg transition-colors">
                    Colaboradores
                </a>
            </nav>
            <div class="ml-auto flex items-center gap-4">
                <span class="text-slate-400 text-sm hidden sm:block">
                    {{ request()->attributes->get('admin')?->correo ?? '' }}
                </span>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-slate-400 hover:text-white text-sm transition-colors">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </header>

        {{-- Contenido principal --}}
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

    </div>

    @stack('scripts')
</body>
</html>
