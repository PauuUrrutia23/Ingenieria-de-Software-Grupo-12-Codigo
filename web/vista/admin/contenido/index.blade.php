<x-admin-layout>
    <x-slot name="header">Panel de Gestión</x-slot>

    <div x-data="moduloContenido()">

    <div class="mb-6 -mt-2">
        <p class="ig-eyebrow mb-2">Contenido del sitio</p>
        <p class="text-mute text-sm">Contenido multimedia de las secciones informativas del sitio público.</p>
    </div>

    @if(session('success'))
        <div class="ig-badge-ok flex items-center gap-3 border px-5 py-3 mb-6 text-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Pestañas de sección: el query param ?seccion= define qué contenido se lista --}}
    <nav class="flex flex-wrap items-end gap-x-6 gap-y-3 mb-6 border-b border-line" aria-label="Secciones de contenido">
        <a href="{{ route('admin.productos.index') }}" class="pb-3 -mb-px border-b-2 border-transparent text-sm text-mute hover:text-carbon">Productos</a>
        @foreach($secciones as $s)
            <a href="{{ route('admin.contenido.index', ['seccion' => $s]) }}"
               class="pb-3 -mb-px border-b-2 text-sm transition-colors
                      {{ $seccionActual === $s ? 'border-wood text-carbon font-medium' : 'border-transparent text-mute hover:text-carbon hover:border-line-strong' }}">
                {{ $nombresSeccion[$s] }}
            </a>
        @endforeach
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <p class="ig-meta">{{ $nombresSeccion[$seccionActual] }} · {{ $contenidos->count() }} {{ $contenidos->count() === 1 ? 'registro' : 'registros' }}</p>
        <a href="{{ route('admin.contenido.create', ['seccion' => $seccionActual]) }}"
           class="ig-btn ig-btn-primary !py-3 !px-5">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Agregar a {{ $nombresSeccion[$seccionActual] }}
        </a>
    </div>

    <div class="ig-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[46rem] text-left">
                <caption class="sr-only">Contenidos de {{ $nombresSeccion[$seccionActual] }}</caption>
                <thead class="bg-paper-deep border-b border-line">
                    <tr>
                        <th scope="col" class="ig-meta px-5 py-3 font-normal">Título</th>
                        <th scope="col" class="ig-meta px-5 py-3 font-normal whitespace-nowrap">Archivo</th>
                        <th scope="col" class="ig-meta px-5 py-3 font-normal text-right whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contenidos as $c)
                    <tr class="border-b border-line last:border-b-0 hover:bg-paper transition-colors">
                        <td class="px-5 py-4 text-sm text-carbon">{{ $c->titulo ?? '—' }}</td>
                        <td class="px-5 py-4 text-sm">
                            @if(!$c->archivo && $c->enlace)
                                <a href="{{ $c->enlace }}" target="_blank" rel="noopener"
                                   class="ig-link-ghost !normal-case !tracking-normal !text-sm">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                    Abrir enlace
                                </a>
                            @elseif($c->archivo)
                                <a href="{{ Storage::url($c->archivo) }}" target="_blank" rel="noopener"
                                   class="ig-link-ghost !normal-case !tracking-normal !text-sm">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                    Ver archivo
                                </a>
                            @else
                                <span class="ig-meta">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex gap-2 items-center justify-end">
                                <a href="{{ route('admin.contenido.edit', $c) }}"
                                   class="w-8 h-8 border border-line text-mute hover:text-carbon hover:border-line-strong flex items-center justify-center transition-colors" aria-label="Editar">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <button type="button"
                                        @click="abrirEliminar({{ $c->id_contenido }}, @js($c->titulo ?? 'este contenido'), @js($nombresSeccion[$seccionActual]))"
                                        class="w-8 h-8 border border-line text-mute hover:text-wood-deep hover:border-line-strong flex items-center justify-center transition-colors" aria-label="Eliminar">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <i data-lucide="layout-panel-left" class="w-12 h-12 text-line-strong mb-3"></i>
                                <p class="text-mute-deep font-medium">Aún no hay contenido en {{ $nombresSeccion[$seccionActual] }}.</p>
                                <p class="text-mute text-sm mt-1">Usa «Agregar a {{ $nombresSeccion[$seccionActual] }}» para crear el primero.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-modal show="eliminar" titulo="Eliminar contenido" ancho="max-w-md">
        <p class="text-mute-deep mb-2 leading-relaxed">
            ¿Confirma que desea eliminar <strong x-text="seleccionado.titulo" class="text-carbon"></strong>
            de <strong x-text="seleccionado.seccion" class="text-carbon"></strong>?
        </p>
        <p class="text-mute text-sm mb-6">
            Se eliminará también el archivo multimedia asociado. Esta acción no se puede deshacer.
        </p>
        <form :action="'{{ url('admin/contenido') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
            @csrf @method('DELETE')
            <button type="button" @click="eliminar = false"
                    class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</button>
            <button type="submit"
                    class="ig-btn ig-btn-primary !py-2.5 !px-5 !bg-wood-deep !border-wood-deep hover:!bg-wood hover:!border-wood">Sí, eliminar</button>
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
