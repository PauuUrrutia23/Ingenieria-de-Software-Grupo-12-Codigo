@props(['titulo' => null, 'descripcion' => null, 'leaflet' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? config('app.name', 'Ingecon') }}</title>
    <meta name="description" content="{{ $descripcion ?? 'Ingecon: industrialización de la madera y construcción prefabricada. Estructuras, conectores metálicos y terminaciones con control de calidad certificado.' }}">
    <meta name="theme-color" content="#111315">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@200;300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if($leaflet)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endif
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="antialiased font-sans text-carbon flex flex-col min-h-screen bg-paper">

    {{-- Menú lateral (se conserva: es la entrada directa a Proyectos,
         Certificaciones y Colaboradores desde cualquier página). --}}
    <div x-data="{ isLateralOpen: false }">
        <button @click="isLateralOpen = true" aria-label="Abrir menú lateral"
                :aria-expanded="isLateralOpen"
                class="fixed bottom-6 left-4 z-[55] bg-carbon text-white border border-carbon px-4 py-3 flex items-center gap-2.5 text-[0.6875rem] font-medium uppercase tracking-[0.16em] hover:bg-wood hover:text-carbon hover:border-wood transition-colors">
            <i data-lucide="layout-panel-left" class="h-4 w-4"></i>
            <span class="hidden sm:inline">Índice</span>
        </button>

        <div x-show="isLateralOpen" x-cloak @click="isLateralOpen = false"
             style="display: none;" class="fixed inset-0 bg-carbon/60 backdrop-blur-[2px] z-[60]"></div>

        <aside x-show="isLateralOpen" x-cloak x-trap.noscroll="isLateralOpen"
               @keydown.escape.window="isLateralOpen = false"
               x-transition:enter="transition duration-500 ease-out"
               x-transition:enter-start="-translate-x-full opacity-0"
               x-transition:enter-end="translate-x-0 opacity-100"
               x-transition:leave="transition duration-300 ease-in"
               x-transition:leave-start="translate-x-0 opacity-100"
               x-transition:leave-end="-translate-x-full opacity-0"
               style="display: none;"
               class="fixed top-0 left-0 h-full w-80 bg-surface z-[70] flex flex-col border-r border-line">
            <div class="flex items-center justify-between px-6 py-6 border-b border-line">
                <span class="ig-meta">Secciones</span>
                <button @click="isLateralOpen = false" aria-label="Cerrar menú lateral"
                        class="text-mute hover:text-carbon transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
                <a href="/proyectos" class="flex items-baseline gap-4 px-3 py-4 text-steel hover:bg-paper-deep transition-colors group">
                    <span class="ig-meta text-wood-deep">01</span>
                    <span class="font-display text-xl font-light tracking-tight group-hover:text-wood-deep transition-colors">Proyectos</span>
                </a>
                <a href="{{ route('public.certificaciones') }}" class="flex items-baseline gap-4 px-3 py-4 text-steel hover:bg-paper-deep transition-colors group">
                    <span class="ig-meta text-wood-deep">02</span>
                    <span class="font-display text-xl font-light tracking-tight group-hover:text-wood-deep transition-colors">Certificaciones</span>
                </a>
                <a href="{{ route('public.colaboradores') }}" class="flex items-baseline gap-4 px-3 py-4 text-steel hover:bg-paper-deep transition-colors group">
                    <span class="ig-meta text-wood-deep">03</span>
                    <span class="font-display text-xl font-light tracking-tight group-hover:text-wood-deep transition-colors">Colaboradores</span>
                </a>
            </nav>
            <p class="px-6 py-5 border-t border-line ig-lede !text-sm">
                Industrialización de la madera desde 1994.
            </p>
        </aside>
    </div>

    {{-- Navbar: transparente sobre el hero, sólido al scrollear. --}}
    <nav x-data="{ isMobileMenuOpen: false }" class="ig-nav">
        <div class="ig-container !px-6 md:!px-12">
            <div class="flex justify-between items-center gap-6">
                <a href="/" class="ig-brand flex items-baseline">
                    INGECON<span class="ig-brand-dot">.</span>
                </a>

                <div class="hidden lg:flex items-center gap-8">
                    <a href="/" class="ig-nav-link" @if(request()->is('/')) aria-current="page" @endif>Nosotros</a>
                    <a href="/#productos" class="ig-nav-link">Productos</a>
                    <a href="/#proyectos" class="ig-nav-link">Proyectos</a>
                    <a href="/#certificaciones" class="ig-nav-link">Certificaciones</a>
                    <a href="{{ route('public.documentacion.conectores') }}" target="_blank" rel="noopener"
                       class="ig-nav-link flex items-center gap-1.5">
                        Conectores Metálicos <i data-lucide="external-link" class="h-3 w-3"></i>
                    </a>

                    <a href="/#contacto" class="ig-nav-cta">Contáctanos</a>

                    @guest
                    <a href="{{ route('login') }}" class="ig-nav-link flex items-center gap-1.5">
                        <i data-lucide="lock" class="h-3.5 w-3.5"></i> Acceso admin
                    </a>
                    @endguest
                </div>

                <div class="lg:hidden flex items-center">
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen"
                            :aria-expanded="isMobileMenuOpen.toString()"
                            aria-controls="menu-movil"
                            :aria-label="isMobileMenuOpen ? 'Cerrar menú' : 'Abrir menú'"
                            class="ig-nav-burger p-2 -mr-2">
                        <i data-lucide="menu" x-show="!isMobileMenuOpen" class="h-7 w-7"></i>
                        <i data-lucide="x" x-show="isMobileMenuOpen" style="display: none;" class="h-7 w-7"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Menú móvil: panel a pantalla completa, no una lista pegada. --}}
        <div id="menu-movil" x-show="isMobileMenuOpen" x-cloak x-trap.noscroll="isMobileMenuOpen"
             @keydown.escape.window="isMobileMenuOpen = false"
             x-transition:enter="transition-opacity duration-400 ease-out"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-250 ease-in"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;"
             class="lg:hidden fixed inset-0 z-40 bg-carbon ig-blueprint flex flex-col justify-center">
            <div class="ig-container !px-6">
                <p class="ig-eyebrow ig-eyebrow-on-dark mb-10">Navegación</p>
                <nav class="flex flex-col gap-6 text-center lg:text-left">
                    <a href="/" @click="isMobileMenuOpen = false"
                       class="font-display text-3xl font-light tracking-tight text-white hover:text-wood-light transition-colors">Nosotros</a>
                    <a href="/#productos" @click="isMobileMenuOpen = false"
                       class="font-display text-3xl font-light tracking-tight text-white hover:text-wood-light transition-colors">Productos</a>
                    <a href="/#proyectos" @click="isMobileMenuOpen = false"
                       class="font-display text-3xl font-light tracking-tight text-white hover:text-wood-light transition-colors">Proyectos</a>
                    <a href="/#certificaciones" @click="isMobileMenuOpen = false"
                       class="font-display text-3xl font-light tracking-tight text-white hover:text-wood-light transition-colors">Certificaciones</a>
                    <a href="{{ route('public.documentacion.conectores') }}" target="_blank" rel="noopener"
                       class="font-display text-3xl font-light tracking-tight text-white hover:text-wood-light transition-colors">Conectores Metálicos</a>
                </nav>
                <div class="mt-12 flex flex-col gap-3">
                    <a href="/#contacto" @click="isMobileMenuOpen = false"
                       class="ig-btn ig-btn-hero justify-center border-wood text-wood-light">
                        <span class="ig-btn-label">Contáctanos</span>
                    </a>
                    @guest
                    <a href="{{ route('login') }}"
                       class="text-center text-white/70 hover:text-white text-sm font-light tracking-wide transition-colors py-2">
                        Acceso admin
                    </a>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow">
        {{ $slot }}
    </main>

    <footer class="bg-carbon text-white ig-blueprint border-t border-white/10">
        <div class="ig-container pt-20 pb-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12 md:gap-8 mb-16">
                <div class="md:col-span-5">
                    <a href="/" class="ig-brand inline-flex items-baseline mb-5">
                        INGECON<span class="ig-brand-dot">.</span>
                    </a>
                    <p class="text-white/55 text-sm font-light leading-relaxed max-w-sm">
                        Industrialización de la madera: estructuras, obra civil prefabricada y
                        terminaciones, con conectores metálicos de fabricación propia.
                    </p>
                </div>

                <div class="md:col-span-3">
                    <h4 class="text-white text-xs uppercase tracking-widest mb-6 font-medium">Navegación</h4>
                    <ul class="space-y-4">
                        <li><a href="/" class="text-white/55 hover:text-white text-sm font-light transition-colors">Nosotros</a></li>
                        <li><a href="/#productos" class="text-white/55 hover:text-white text-sm font-light transition-colors">Productos</a></li>
                        <li><a href="/#proyectos" class="text-white/55 hover:text-white text-sm font-light transition-colors">Proyectos</a></li>
                        <li><a href="{{ route('public.proyectos.index') }}" class="text-white/55 hover:text-white text-sm font-light transition-colors">Galería completa</a></li>
                    </ul>
                </div>

                <div class="md:col-span-4">
                    <h4 class="text-white text-xs uppercase tracking-widest mb-6 font-medium">Compañía</h4>
                    <ul class="space-y-4">
                        <li><a href="{{ route('public.certificaciones') }}" class="text-white/55 hover:text-white text-sm font-light transition-colors">Certificaciones</a></li>
                        <li><a href="{{ route('public.colaboradores') }}" class="text-white/55 hover:text-white text-sm font-light transition-colors">Colaboradores</a></li>
                        <li><a href="{{ route('public.producto') }}" class="text-white/55 hover:text-white text-sm font-light transition-colors">Conectores metálicos</a></li>
                        <li><a href="/#contacto" class="text-white/55 hover:text-white text-sm font-light transition-colors">Contactar</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-white/45 text-xs font-light">&copy; {{ date('Y') }} Ingecon. Todos los derechos reservados.</p>
                <div class="flex items-center gap-6 text-xs text-white/45 font-light">
                    @php $urlUbicacion = $ubicacionUrl ?? null; @endphp
                    @if($urlUbicacion)
                        <a href="{{ $urlUbicacion }}" target="_blank" rel="noopener"
                           class="hover:text-white transition-colors">Ubicación</a>
                    @endif
                    @php $urlTerminos = $terminosUrl ?? null; @endphp
                    <a href="{{ $urlTerminos ?: route('terminos') }}" target="_blank" rel="noopener"
                       class="hover:text-white transition-colors">Términos y Condiciones y Política de Privacidad</a>
                    <button type="button" data-ig-motion-toggle aria-pressed="false"
                            class="hover:text-white transition-colors border border-white/15 px-3 py-1.5 uppercase tracking-[0.14em] text-[0.625rem]">
                        Ver animaciones
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
