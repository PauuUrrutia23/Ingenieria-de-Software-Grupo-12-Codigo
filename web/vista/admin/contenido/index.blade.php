<x-admin-layout>
    <x-slot name="header">Panel de Gestión</x-slot>

    {{-- CU 34.5: panel de gestión de contenido multimedia.
         RF44 / CU 44.1-44.5: la eliminación se confirma en Ventana Modal. --}}
    <div x-data="moduloContenido()">

    <p class="text-slate-500 text-sm -mt-2 mb-6">Contenido multimedia de las secciones informativas del sitio público.</p>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium px-5 py-3 rounded-xl">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabs de secciones --}}
    <div class="flex flex-wrap gap-2 mb-6 border-b border-slate-200 pb-4">
        @foreach($secciones as $s)
            <a href="{{ route('admin.contenido.index', ['seccion' => $s]) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                      {{ $seccionActual === $s ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                {{ $nombresSeccion[$s] }}
            </a>
        @endforeach
    </div>

    <div class="mb-6">
        <a href="{{ route('admin.contenido.create', ['seccion' => $seccionActual]) }}"
           class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Agregar a {{ $nombresSeccion[$seccionActual] }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Archivo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($contenidos as $c)
                <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $c->titulo ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($c->archivo)
                            <a href="{{ Storage::url($c->archivo) }}" target="_blank" class="text-slate-600 hover:text-slate-900 hover:underline">Ver archivo</a>
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2 items-center">
                            {{-- RF43 (actualizar): formulario precargado --}}
                            <a href="{{ route('admin.contenido.edit', $c) }}"
                               class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Editar">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            {{-- RF44 / CU 44.1: ícono "Eliminar" + Ventana Modal de confirmación --}}
                            <button type="button"
                                    @click="abrirEliminar({{ $c->id_contenido }}, @js($c->titulo ?? 'este contenido'), @js($nombresSeccion[$seccionActual]))"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" aria-label="Eliminar">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <i data-lucide="layout-panel-left" class="w-12 h-12 text-slate-300 mb-3"></i>
                            <p class="text-slate-500 font-medium">Aún no hay contenido en {{ $nombresSeccion[$seccionActual] }}.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- RF44 / CU 44.1-44.5 - Confirmación previa a eliminar contenido multimedia --}}
    <x-modal show="eliminar" titulo="Eliminar contenido" ancho="max-w-md">
        <p class="text-slate-700 mb-2">
            ¿Confirma que desea eliminar <strong x-text="seleccionado.titulo"></strong>
            de <strong x-text="seleccionado.seccion"></strong>?
        </p>
        <p class="text-sm text-slate-400 mb-6">
            Se eliminará también el archivo multimedia asociado. Esta acción no se puede deshacer.
        </p>
        <form :action="'{{ url('admin/contenido') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
            @csrf @method('DELETE')
            <button type="button" @click="eliminar = false"
                    class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
            <button type="submit"
                    class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Sí, eliminar</button>
        </form>
    </x-modal>
    </div>

    <script>
      function moduloContenido() {
        return {
          eliminar: false,
          seleccionado: { id: null, titulo: '', seccion: '' },

          init() {
            this.$watch('eliminar', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
          },

          abrirEliminar(id, titulo, seccion) {
            this.seleccionado = { id, titulo, seccion };
            this.eliminar = true;
          },
        };
      }
    </script>
</x-admin-layout>
