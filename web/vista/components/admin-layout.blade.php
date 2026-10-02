<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111315">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel de Gestión - {{ config('app.name', 'Ingecon') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@200;300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full antialiased font-sans bg-paper text-carbon">

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

    <div x-data="{ navMovil: false }" class="lg:flex min-h-screen">

        {{-- Barra superior: sólo móvil --}}
        <header class="lg:hidden sticky top-0 z-40 flex items-center justify-between gap-4 bg-surface/95 backdrop-blur-md border-b border-line px-4 py-3">
            <a href="/admin/dashboard" class="flex items-baseline gap-1.5 font-display text-lg tracking-[0.28em] text-carbon">
                INGECON<span class="text-wood-deep">.</span>
            </a>
            <button type="button" @click="navMovil = true" aria-controls="nav-lateral"
                    :aria-expanded="navMovil.toString()" aria-label="Abrir menú del panel"
                    class="p-2 -mr-1 text-carbon hover:text-wood-deep transition-colors">
                <i data-lucide="menu" class="h-6 w-6"></i>
            </button>
        </header>

        {{-- Velo del menú móvil --}}
        <div x-show="navMovil" x-cloak @click="navMovil = false" style="display: none;"
             class="fixed inset-0 z-[45] bg-carbon/55 backdrop-blur-[2px] lg:hidden" aria-hidden="true"></div>

        {{-- Costado de navegación: cajón fuera de línea en móvil, fijo en escritorio.
             Se usan variantes max-lg: para que el estado de Alpine nunca compita
             con la posición en escritorio (class swapping entre breakpoints falla). --}}
        <aside id="nav-lateral"
               :class="navMovil ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full max-lg:invisible'"
               class="fixed lg:sticky top-0 left-0 z-50 lg:z-auto h-screen w-[17.5rem] shrink-0
                      max-lg:-translate-x-full
                      bg-paper-deep border-r border-line flex flex-col
                      transition-transform duration-400 ease-premium lg:transition-none">

            <div class="flex items-start justify-between gap-3 px-5 h-[4.5rem] border-b border-line">
                <a href="/admin/dashboard" class="flex items-baseline gap-1.5 font-display text-lg tracking-[0.28em] text-carbon leading-none">
                    INGECON<span class="text-wood-deep">.</span>
                </a>
                <button type="button" @click="navMovil = false" aria-label="Cerrar menú del panel"
                        class="lg:hidden p-1.5 -mr-1.5 text-mute hover:text-carbon transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <p class="px-5 py-3 ig-meta border-b border-line">Panel de gestión</p>

            <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-4" aria-label="Menú del panel de gestión">
                @foreach ($items as $item)
                    <a href="{{ $item['href'] }}"
                       @click="navMovil = false"
                       @if($item['active']) aria-current="page" @endif
                       class="relative flex items-center gap-3 px-3 py-2.5 mb-1 text-[0.8125rem] font-medium tracking-[0.01em] transition-colors
                              {{ $item['active'] ? 'bg-carbon text-white' : 'text-steel hover:text-carbon hover:bg-surface' }}">
                        <i data-lucide="{{ $item['icon'] }}"
                           class="h-[17px] w-[17px] shrink-0 {{ $item['active'] ? 'text-wood-light' : 'text-mute' }}"></i>
                        <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                        @if($item['active'])
                            <span class="absolute left-0 top-0 h-full w-[3px] bg-wood" aria-hidden="true"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="px-3 py-4 border-t border-line">
                <div class="px-3 pb-3 mb-1">
                    <p class="ig-meta mb-1">Sesión</p>
                    <p class="text-[0.8125rem] text-mute-deep truncate" title="{{ Auth::user()->correo }}">{{ Auth::user()->correo }}</p>
                </div>
                <a href="{{ route('admin.password.edit') }}"
                   @click="navMovil = false"
                   class="relative flex items-center gap-3 px-3 py-2.5 mb-1 text-[0.8125rem] font-medium transition-colors
                          {{ request()->is('admin/password') ? 'bg-surface text-carbon' : 'text-steel hover:text-carbon hover:bg-surface' }}">
                    <i data-lucide="key-round" class="h-[17px] w-[17px] shrink-0 text-mute"></i>
                    <span class="min-w-0 truncate">Cambiar contraseña</span>
                    @if(request()->is('admin/password'))
                        <span class="absolute left-0 top-0 h-full w-[3px] bg-wood" aria-hidden="true"></span>
                    @endif
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-3 py-2.5 text-[0.8125rem] font-medium text-left transition-colors
                                   text-[#8c2f2f] hover:bg-surface">
                        <i data-lucide="log-out" class="h-[17px] w-[17px] shrink-0"></i>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        {{-- Contenido --}}
        <main class="flex-1 min-w-0">
            <div class="max-w-6xl px-5 sm:px-8 lg:px-10 py-8 lg:py-12">
                <header class="mb-8 lg:mb-10">
                    <p class="ig-eyebrow mb-3">Administración</p>
                    <h1 class="font-display text-[1.75rem] sm:text-[2.125rem] font-light tracking-[-0.02em] leading-tight text-carbon">
                        {{ $header ?? 'Dashboard' }}
                    </h1>
                </header>
                <div class="ig-rule mb-8" aria-hidden="true"></div>

                {{ $slot }}
            </div>
        </main>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
