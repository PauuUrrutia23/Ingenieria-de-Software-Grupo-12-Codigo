<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    @php
        $accesos = [
            ['route' => route('admin.proyectos.index'), 'icon' => 'hard-hat', 'titulo' => 'Gestión de proyectos', 'desc' => 'Portafolio de obras: alta, edición y visibilidad.'],
            ['route' => route('admin.certificados.index'), 'icon' => 'shield-check', 'titulo' => 'Certificados', 'desc' => 'Normativas y certificaciones vigentes.'],
            ['route' => route('admin.colaboradores.index'), 'icon' => 'handshake', 'titulo' => 'Colaboradores', 'desc' => 'Empresas y marcas asociadas.'],
            ['route' => route('admin.consultas.index'), 'icon' => 'inbox', 'titulo' => 'Módulo comercial', 'desc' => 'Historial de consultas recibidas.'],
            ['route' => route('admin.contenido.index'), 'icon' => 'layout-panel-left', 'titulo' => 'Panel de gestión', 'desc' => 'Contenido multimedia del sitio público.'],
        ];
    @endphp

    {{-- Estado de la sesión: sólo lo que la vista realmente sabe. --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 items-start">
        <div class="lg:col-span-7">
            <p class="ig-lede max-w-prose">
                Sesión iniciada como <span class="text-carbon font-medium">{{ Auth::user()->correo }}</span>.
                Desde este panel se administra el contenido del sitio público de Ingecon.
            </p>
        </div>
        <dl class="lg:col-span-5 ig-card grid grid-cols-2 divide-x divide-line">
            <div class="px-4 py-3.5">
                <dt class="ig-meta mb-1.5">Rol</dt>
                <dd class="text-[0.9375rem] text-carbon">{{ Auth::user()->rol }}</dd>
            </div>
            <div class="px-4 py-3.5">
                <dt class="ig-meta mb-1.5">Módulos</dt>
                <dd class="text-[0.9375rem] text-carbon font-mono tabular-nums">{{ str_pad((string) count($accesos), 2, '0', STR_PAD_LEFT) }}</dd>
            </div>
        </dl>
    </div>

    <div class="ig-rule my-9 lg:my-11" aria-hidden="true"></div>

    <div class="flex items-baseline justify-between gap-6 mb-5">
        <h2 class="font-display text-xl sm:text-[1.375rem] font-light tracking-[-0.015em] text-carbon">Accesos directos</h2>
        <p class="ig-meta hidden sm:block">Áreas de administración</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-line">
        @foreach ($accesos as $i => $a)
            <a href="{{ $a['route'] }}"
               class="ig-reveal ig-d{{ min($i + 1, 4) }} group relative bg-surface px-5 py-6 sm:px-6 flex items-start gap-4 transition-colors hover:bg-paper-deep
                      {{ ($loop->last && count($accesos) % 2 === 1) ? 'sm:col-span-2' : '' }}">
                <span class="shrink-0 w-10 h-10 bg-carbon text-wood-light flex items-center justify-center transition-colors group-hover:bg-wood group-hover:text-carbon">
                    <i data-lucide="{{ $a['icon'] }}" class="h-[18px] w-[18px]"></i>
                </span>
                <span class="min-w-0">
                    <span class="flex items-baseline gap-2.5">
                        <span class="ig-meta shrink-0">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="font-display text-[1.0625rem] font-light tracking-[-0.01em] text-carbon truncate">{{ $a['titulo'] }}</span>
                    </span>
                    <span class="block mt-1.5 text-[0.8125rem] leading-relaxed text-mute max-w-md">{{ $a['desc'] }}</span>
                </span>
                <i data-lucide="arrow-right"
                   class="absolute right-5 top-6 h-4 w-4 text-line-strong transition-all duration-300 ease-steel group-hover:text-wood-deep group-hover:translate-x-1"></i>
            </a>
        @endforeach
    </div>

    <p class="mt-8 ig-meta">
        © {{ date('Y') }} {{ config('app.name', 'Ingecon') }}
    </p>
</x-admin-layout>
