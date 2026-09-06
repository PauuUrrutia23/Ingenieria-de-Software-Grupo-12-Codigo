<x-admin-layout>
    <x-slot name="header">Certificados</x-slot>

    {{-- CU 34.6: módulo de Certificados. RF26 (registrar) opera en Ventana Modal. --}}
    <div x-data="moduloCertificados()">

        <div class="flex items-center justify-between mb-8 -mt-2">
            <p class="text-slate-500 text-sm">Normativas y certificaciones vigentes.</p>
            <button type="button" @click="crear = true"
                    class="flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Certificado
            </button>
        </div>

        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium px-5 py-3 rounded-xl">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm px-5 py-3 rounded-xl">
                <ul class="list-disc list-inside">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Organismo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($certificados as $c)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $c->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $c->organismo }}</td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full
                                {{ match($c->estado) {
                                    'vigente' => 'bg-green-100 text-green-700',
                                    'vencido' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-slate-100 text-slate-600',
                                } }}">{{ ucfirst($c->estado) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2 items-center">
                                <a href="{{ route('admin.certificados.edit', $c) }}"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Editar {{ $c->nombre }}">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <button type="button"
                                        @click="abrirEliminar({{ $c->id_certificado }}, @js($c->nombre))"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" aria-label="Eliminar {{ $c->nombre }}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    {{-- CU 34.6 Excepción 3 --}}
                    <tr><td colspan="5" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <i data-lucide="shield-check" class="w-12 h-12 text-slate-300 mb-3"></i>
                            <p class="text-slate-500 font-medium">Aún no hay registros.</p>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-slate-100">{{ $certificados->links() }}</div>
        </div>

        {{-- RF26 / CU 26.1 - Registrar Certificación en Ventana Modal --}}
        <x-modal show="crear" titulo="Nuevo Certificado" ancho="max-w-2xl">
            <form action="{{ route('admin.certificados.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="cert-nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de la Normativa</label>
                        <input id="cert-nombre" type="text" name="nombre" value="{{ old('nombre') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                    </div>
                    <div>
                        <label for="cert-organismo" class="block text-sm font-semibold text-slate-700 mb-1.5">Organismo Certificador</label>
                        <input id="cert-organismo" type="text" name="organismo" value="{{ old('organismo') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                    </div>
                    <div>
                        <label for="cert-url" class="block text-sm font-semibold text-slate-700 mb-1.5">Enlace del Organismo</label>
                        <input id="cert-url" type="url" name="url_organismo" value="{{ old('url_organismo') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                    </div>
                </div>
                <div>
                    <label for="cert-desc" class="block text-sm font-semibold text-slate-700 mb-1.5">Descripción</label>
                    <textarea id="cert-desc" name="descripcion" rows="3"
                              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 resize-none">{{ old('descripcion') }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="cert-imagen" class="block text-sm font-semibold text-slate-700 mb-1.5">Imagen Representativa</label>
                        <input id="cert-imagen" type="file" name="imagen" accept="image/*"
                               class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <p class="text-xs text-slate-400 mt-1.5">Máximo 2 MB (RNF17).</p>
                    </div>
                    <div>
                        <label for="cert-pdf" class="block text-sm font-semibold text-slate-700 mb-1.5">Archivo PDF adjunto (opcional)</label>
                        <input id="cert-pdf" type="file" name="archivo_pdf" accept="application/pdf"
                               class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <p class="text-xs text-slate-400 mt-1.5">Se valida el formato PDF real, no la extensión (RNF04).</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="crear = false"
                            class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Guardar</button>
                </div>
            </form>
        </x-modal>

        {{-- Confirmación de eliminación en Ventana Modal --}}
        <x-modal show="eliminar" titulo="Eliminar Certificado" ancho="max-w-md">
            <p class="text-slate-700 mb-6">
                ¿Confirma que desea eliminar <strong x-text="seleccionado.nombre"></strong>?
                Se borrará también su imagen y su PDF asociado.
            </p>
            <form :action="'{{ url('admin/certificados') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" @click="eliminar = false"
                        class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Sí, eliminar</button>
            </form>
        </x-modal>
    </div>

    <script>
      function moduloCertificados() {
        return {
          crear: false,
          eliminar: false,
          seleccionado: { id: null, nombre: '' },

          init() {
            // CU 26.1 Exc. 1-4: el formulario permanece abierto con lo ya ingresado.
            @if($errors->any() && old('_modal') === 'crear')
              this.crear = true;
            @endif
            ['crear', 'eliminar'].forEach(v => {
              this.$watch(v, () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            });
          },

          abrirEliminar(id, nombre) {
            this.seleccionado = { id, nombre };
            this.eliminar = true;
          },
        };
      }
    </script>
</x-admin-layout>
