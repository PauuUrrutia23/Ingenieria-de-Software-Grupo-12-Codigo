{{--
  Ventana Modal reutilizable del Panel de Gestión.

  Varios requerimientos (RF26, RF39, RF44, RF45, RF46, RF48, RF49, RF51) piden
  explícitamente que el formulario o la confirmación se presenten en una
  "Ventana Modal", no en una página aparte. Este componente centraliza ese patrón.

  Uso:
    <div x-data="{ abierto: false }">
      <button @click="abierto = true">Abrir</button>
      <x-modal show="abierto" titulo="Nuevo registro"> ... </x-modal>
    </div>

  `show` es el nombre de la propiedad Alpine del contenedor padre que controla
  la visibilidad; el componente la pone en false al cerrar.
--}}
@props(['show', 'titulo' => '', 'ancho' => 'max-w-2xl'])

<div x-show="{{ $show }}" style="display: none;"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[80] flex items-center justify-center p-4"
     @keydown.escape.window="{{ $show }} = false"
     role="dialog" aria-modal="true">

    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="{{ $show }} = false" aria-hidden="true"></div>

    <div @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative z-10 bg-white rounded-2xl shadow-2xl w-full {{ $ancho }} max-h-[90vh] overflow-y-auto flex flex-col">

        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="text-lg font-bold text-slate-900">{{ $titulo }}</h3>
            <button type="button" @click="{{ $show }} = false" aria-label="Cerrar ventana"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5">
            {{ $slot }}
        </div>
    </div>
</div>
