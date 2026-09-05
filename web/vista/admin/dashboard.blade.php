<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    <p class="text-slate-500 text-sm -mt-6 mb-8">
        Bienvenido, {{ Auth::user()->correo }}
        <span class="inline-block ml-2 bg-slate-100 text-slate-600 text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase tracking-wide">{{ Auth::user()->rol }}</span>
    </p>

    @php
        $accesos = [
            ['route' => route('admin.proyectos.index'), 'icon' => 'hard-hat', 'titulo' => 'Gestión de proyectos', 'desc' => 'Portafolio de obras: alta, edición y visibilidad.'],
            ['route' => route('admin.certificados.index'), 'icon' => 'shield-check', 'titulo' => 'Certificados', 'desc' => 'Normativas y certificaciones vigentes.'],
            ['route' => route('admin.colaboradores.index'), 'icon' => 'handshake', 'titulo' => 'Colaboradores', 'desc' => 'Empresas y marcas asociadas.'],
            ['route' => route('admin.consultas.index'), 'icon' => 'inbox', 'titulo' => 'Módulo comercial', 'desc' => 'Historial de consultas recibidas.'],
            ['route' => route('admin.contenido.index'), 'icon' => 'layout-panel-left', 'titulo' => 'Panel de gestión', 'desc' => 'Contenido multimedia del sitio público.'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        @foreach ($accesos as $a)
            <a href="{{ $a['route'] }}"
               class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $a['icon'] }}" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-900 text-lg group-hover:text-slate-700 transition-colors">{{ $a['titulo'] }}</h2>
                        <p class="text-slate-500 text-sm">{{ $a['desc'] }}</p>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</x-admin-layout>