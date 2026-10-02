<x-admin-layout>
    <x-slot name="header">Certificados</x-slot>

    <div x-data="moduloCertificados()">

        <div class="flex flex-wrap items-end justify-between gap-4 pb-6">
            <div>
                <p class="ig-eyebrow mb-2">Cumplimiento</p>
                <p class="ig-lede max-w-xl">Normativas y certificaciones vigentes de Ingecon.</p>
            </div>
            <button type="button" @click="crear = true" class="ig-btn ig-btn-primary shrink-0">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Nuevo Certificado
            </button>
        </div>
        <div class="ig-rule mb-8"></div>

        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 border border-line bg-surface px-5 py-3 text-sm text-carbon">
                <i data-lucide="check-circle-2" class="h-5 w-5 shrink-0 text-wood-deep"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 border border-[#b4403f] bg-surface px-5 py-3 text-sm text-[#8c2f2f]">
                <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="ig-card overflow-x-auto">
            <table class="min-w-full">
                <thead class="border-b border-line-strong bg-paper">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left font-mono text-[0.625rem] uppercase tracking-[0.16em] text-mute">Nombre</th>
                        <th scope="col" class="px-6 py-3 text-left font-mono text-[0.625rem] uppercase tracking-[0.16em] text-mute">Organismo</th>
                        <th scope="col" class="px-6 py-3 text-left font-mono text-[0.625rem] uppercase tracking-[0.16em] text-mute">Estado</th>
                        <th scope="col" class="px-6 py-3 text-right font-mono text-[0.625rem] uppercase tracking-[0.16em] text-mute">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($certificados as $c)
                    <tr class="border-b border-line transition-colors last:border-b-0 hover:bg-paper/70">
                        <td class="px-6 py-4 text-sm font-medium text-carbon">{{ $c->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-mute-deep">{{ $c->organismo }}</td>
                        <td class="px-6 py-4">
                            <span class="ig-badge
                                {{ match($c->estado) {
                                    'vigente' => 'ig-badge-ok',
                                    'vencido' => 'ig-badge-warn',
                                    default => 'ig-badge-mute',
                                } }}">{{ ucfirst($c->estado) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1.5 items-center justify-end">
                                <a href="{{ route('admin.certificados.edit', $c) }}"
                                   class="inline-flex h-8 w-8 items-center justify-center border border-transparent text-mute transition-colors hover:border-line-strong hover:bg-surface hover:text-carbon" aria-label="Editar {{ $c->nombre }}">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <button type="button"
                                        @click="abrirEliminar({{ $c->id_certificado }}, @js($c->nombre))"
                                        class="inline-flex h-8 w-8 items-center justify-center border border-transparent text-mute transition-colors hover:border-[#b4403f] hover:text-[#8c2f2f]" aria-label="Eliminar {{ $c->nombre }}">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <i data-lucide="shield-check" class="w-12 h-12 text-line-strong mb-3"></i>
                            <p class="text-carbon font-medium">Aún no hay registros.</p>
                        </div>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-line px-6 py-4">{{ $certificados->links() }}</div>
        </div>

        <x-modal show="crear" titulo="Nuevo Certificado" ancho="max-w-2xl">
            <form action="{{ route('admin.certificados.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="cert-nombre" class="ig-label">Nombre de la Normativa</label>
                        <input id="cert-nombre" type="text" name="nombre" value="{{ old('nombre') }}"
                               class="ig-field-box" required>
                    </div>
                    <div>
                        <label for="cert-organismo" class="ig-label">Organismo Certificador</label>
                        <input id="cert-organismo" type="text" name="organismo" value="{{ old('organismo') }}"
                               class="ig-field-box" required>
                    </div>
                    <div>
                        <label for="cert-url" class="ig-label">Enlace del Organismo</label>
                        <input id="cert-url" type="url" name="url_organismo" value="{{ old('url_organismo') }}"
                               class="ig-field-box">
                    </div>
                </div>
                <div>
                    <label for="cert-desc" class="ig-label">Descripción</label>
                    <textarea id="cert-desc" name="descripcion" rows="3"
                              class="ig-field-box resize-y">{{ old('descripcion') }}</textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="cert-imagen" class="ig-label">Imagen Representativa</label>
                        <input id="cert-imagen" type="file" name="imagen" accept="image/*"
                               class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong">
                        <p class="text-xs text-mute mt-1.5">Máximo 2 MB (RNF17).</p>
                    </div>
                    <div>
                        <label for="cert-pdf" class="ig-label">Archivo PDF adjunto (opcional)</label>
                        <input id="cert-pdf" type="file" name="archivo_pdf" accept="application/pdf"
                               class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong">
                        <p class="text-xs text-mute mt-1.5">Se valida el formato PDF real, no la extensión (RNF04).</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="crear = false"
                            class="ig-btn ig-btn-secondary">Cancelar</button>
                    <button type="submit"
                            class="ig-btn ig-btn-primary">Guardar</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="eliminar" titulo="Eliminar Certificado" ancho="max-w-md">
            <p class="text-carbon mb-6">
                ¿Confirma que desea eliminar <strong x-text="seleccionado.nombre"></strong>?
                Se borrará también su imagen y su PDF asociado.
            </p>
            <form :action="'{{ url('admin/certificados') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" @click="eliminar = false"
                        class="ig-btn ig-btn-secondary">Cancelar</button>
                <button type="submit"
                        class="ig-btn border border-[#b4403f] bg-[#8c2f2f] text-white hover:bg-[#7a2828] hover:border-[#7a2828]">Sí, eliminar</button>
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
