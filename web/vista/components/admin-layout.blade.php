<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de Gestión - {{ config('app.name', 'Ingecon') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
{{--
    Menú Lateral del Panel de Gestión (RF34 / CU 34.1): los 5 accesos exigidos
    ("Módulo comercial", "Gestión de proyectos", "Colaboradores", "Panel de
    gestión", "Certificados") con el rótulo textual exacto del requerimiento.
    Paleta y componentes (slate, cards rounded-2xl, iconos atenuados) tomados
    como base visual del panel de administración de referencia del proyecto.
--}}
<body class="h-full antialiased bg-slate-50 text-slate-800 flex">

    @php
        $navItem = function (string $pattern, string $icon, string $label, string $href) {
            $active = request()->is($pattern);
            return compact('active', 'icon', 'label', 'href');
        };
        $items = [
            $navItem('admin/dashboard', 'layout-dashboard', 'Dashboard', '/admin/dashboard'),
            $navItem('admin/proyectos*', 'hard-hat', 'Gestión de proyectos', route('admin.proyectos.index')),
            $navItem('admin/certificados*', 'shield-check', 'Certificados', route('admin.certificados.index')),
            $navItem('admin/colaboradores*', 'handshake', 'Colaboradores', route('admin.colaboradores.index')),
            $navItem('admin/consultas*', 'inbox', 'Módulo comercial', route('admin.consultas.index')),
            $navItem('admin/contenido*', 'layout-panel-left', 'Panel de gestión', route('admin.contenido.index')),
        ];
    @endphp

    <aside class="w-64 h-screen sticky top-0 bg-white border-r border-slate-100 flex flex-col shrink-0">
        {{-- Marca --}}
        <div class="flex items-center gap-2.5 px-5 py-5 border-b border-slate-100">
            <div class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center shrink-0" aria-hidden="true">
                <span class="text-white text-xs font-black tracking-tighter">IC</span>
            </div>
            <div class="min-w-0">
                <p class="text-slate-900 font-bold text-sm leading-tight truncate">INGECON</p>
                <p class="text-slate-400 text-xs leading-tight truncate">Panel de Gestión</p>
            </div>
        </div>

        {{-- Accesos del Menú Lateral (RF34) --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto" aria-label="Menú del panel de gestión">
            @foreach ($items as $item)
                <a href="{{ $item['href'] }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ $item['active'] ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <i data-lucide="{{ $item['icon'] }}" class="w-[18px] h-[18px] shrink-0 {{ $item['active'] ? 'text-white' : 'text-slate-400' }}"></i>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Cuenta --}}
        <div class="px-3 py-4 border-t border-slate-100 space-y-0.5">
            <p class="px-3.5 text-xs text-slate-400 truncate mb-1" title="{{ Auth::user()->correo }}">{{ Auth::user()->correo }}</p>
            <a href="{{ route('admin.password.edit') }}"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->is('admin/password') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i data-lucide="key-round" class="w-[18px] h-[18px] shrink-0 text-slate-400"></i>
                Cambiar contraseña
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors text-left">
                    <i data-lucide="log-out" class="w-[18px] h-[18px] shrink-0"></i>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- Contenido Principal --}}
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="max-w-6xl mx-auto px-6 sm:px-10 py-8">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900">{{ $header ?? 'Dashboard' }}</h1>
            </header>
            {{ $slot }}
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>
