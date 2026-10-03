<x-admin-layout>
    <x-slot name="header">Colaboradores</x-slot>

    <div x-data="moduloColaboradores()">

        {{-- Encabezado de página --}}
        <div class="flex flex-wrap items-end justify-between gap-4 mb-8 -mt-2">
            <div class="max-w-xl">
                <p class="ig-eyebrow mb-2">Alianzas</p>
                <p class="ig-lede text-sm leading-relaxed">Empresas y marcas asociadas a los proyectos de Ingecon.</p>
            </div>
            <button type="button" @click="crear = true"
                    class="ig-btn ig-btn-primary !py-3 !px-5 shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Agregar Colaborador
            </button>
        </div>

        @if(session('success'))
            <div class="ig-badge-ok flex items-center gap-3 border px-5 py-3 mb-6 text-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-paper-deep border border-line-strong text-wood-deep text-sm px-5 py-3 mb-6">
                <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif
        <p x-show="aviso" x-cloak x-text="aviso" role="status" class="text-sm text-wood-deep mb-5"></p>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4" role="list" aria-label="Listado de colaboradores">
            @forelse($colaboradores as $c)
            <article class="ig-card ig-card-hover relative group bg-surface p-5 text-center" role="listitem">
                <button type="button"
                        @click="abrirEliminar({{ $c->id_colaborador }}, @js($c->nombre_comercial))"
                        aria-label="Eliminar {{ $c->nombre_comercial }}"
                        class="absolute top-2 right-2 w-7 h-7 bg-surface text-mute hover:text-wood-deep hover:border-line-strong border border-line flex items-center justify-center opacity-0 group-hover:opacity-100 focus:opacity-100 transition-all">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>

                <button type="button"
                        @click="abrirEditar({{ $c->id_colaborador }})"
                        class="w-full">
                    <div class="relative w-24 h-24 mx-auto mb-3 border border-line overflow-hidden">
                        <span class="ig-plate" aria-hidden="true"></span>
                        <img src="{{ $c->logo_url }}" alt="{{ $c->nombre_comercial }}" class="w-full h-full object-contain p-2 relative z-10">
                    </div>
                    <p class="text-carbon text-sm leading-snug line-clamp-2" title="{{ $c->nombre_comercial }}">{{ $c->nombre_comercial }}</p>
                    <p class="ig-meta mt-2 opacity-0 group-hover:opacity-100 transition-opacity">Editar</p>
                </button>
            </article>
            @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <i data-lucide="handshake" class="w-14 h-14 text-line-strong mb-4"></i>
                <p class="text-mute-deep font-medium mb-1">Aún no hay registros</p>
                <p class="text-mute text-sm mb-5">Agrega el primer colaborador usando el botón superior.</p>
            </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $colaboradores->links() }}</div>

        <x-modal show="crear" titulo="Agregar Colaborador" ancho="max-w-md" :errores="old('_modal') === 'crear'">
            <form action="{{ route('admin.colaboradores.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div>
                    <label for="crear-nombre" class="ig-label">Nombre Comercial</label>
                    <input id="crear-nombre" type="text" name="nombre_comercial" value="{{ old('_modal') === 'crear' ? old('nombre_comercial') : '' }}"
                           class="ig-field-box" required>
                </div>
                <div x-data="{ nombreArchivo: '', vistaPrevia: '' }">
                    <label for="crear-logo" class="ig-label">Logotipo</label>
                    <label for="crear-logo" class="flex flex-col items-center justify-center gap-2 border border-dashed border-line-strong hover:border-wood bg-paper-deep p-6 cursor-pointer transition-colors text-center">
                        <span x-show="!vistaPrevia"><i data-lucide="upload" class="w-7 h-7 text-mute"></i></span>
                        <img x-show="vistaPrevia" style="display:none;" :src="vistaPrevia" alt=""
                             class="h-16 max-w-full object-contain">
                        <span class="text-sm text-mute-deep" :class="nombreArchivo ? 'font-medium' : ''"
                              x-text="nombreArchivo || 'Haz clic para seleccionar el logotipo'"></span>
                        <span class="ig-meta" x-text="nombreArchivo ? 'Haz clic para elegir otro' : 'Máximo 500 KB (RNF17)'"></span>
                    </label>
                    <input id="crear-logo" type="file" name="logotipo" accept="image/jpeg,image/png" class="sr-only" required
                           @change="nombreArchivo = $event.target.files[0] ? $event.target.files[0].name : '';
                                    vistaPrevia = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : ''">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="crear = false"
                            class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</button>
                    <button type="submit"
                            class="ig-btn ig-btn-primary !py-2.5 !px-5">Guardar</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="editar" titulo="Editar Colaborador" ancho="max-w-md" :errores="old('_modal') === 'editar'">
            <form :action="'{{ url('admin/colaboradores') }}/' + seleccionado.id" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="editar">
                <div>
                    <label for="editar-nombre" class="ig-label">Nombre Comercial</label>
                    <input id="editar-nombre" type="text" name="nombre_comercial" x-model="seleccionado.nombre"
                           class="ig-field-box" required>
                </div>
                <div>
                    <span class="ig-label block">Logotipo actual</span>
                    <img :src="seleccionado.logo" alt="" class="h-14 object-contain border border-line p-1 mb-3 bg-paper-deep">
                    <label for="editar-logo" class="ig-label">Reemplazar logotipo (opcional)</label>
                    <input id="editar-logo" type="file" name="logotipo" accept="image/jpeg,image/png"
                           class="ig-field-box cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:border-0 file:text-xs file:uppercase file:tracking-wider file:bg-carbon file:text-surface hover:file:bg-wood hover:file:text-carbon">
                    <p class="text-xs text-mute mt-2">Máximo 500 KB. Si no adjuntas nada, se conserva el actual.</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="editar = false"
                            class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</button>
                    <button type="submit"
                            class="ig-btn ig-btn-primary !py-2.5 !px-5">Guardar cambios</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="eliminar" titulo="Eliminar Colaborador" ancho="max-w-md">
            <p class="text-mute-deep mb-6 leading-relaxed">
                ¿Confirma que desea eliminar a <strong x-text="seleccionado.nombre" class="text-carbon font-semibold"></strong>?
                Esta acción no se puede deshacer.
            </p>
            <form :action="'{{ url('admin/colaboradores') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" @click="eliminar = false"
                        class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</button>
                <button type="submit"
                        class="ig-btn ig-btn-primary !py-2.5 !px-5 !bg-wood-deep !border-wood-deep hover:!bg-wood hover:!border-wood">Sí, eliminar</button>
            </form>
        </x-modal>
    </div>

    <script>
      function moduloColaboradores() {
        return {
          crear: false,
          editar: false,
          eliminar: false,
          aviso: '',
          seleccionado: { id: null, nombre: '', logo: '' },

          init() {
            @if($errors->any() && old('_modal') === 'crear')
              this.crear = true;
            @endif
            ['crear', 'editar', 'eliminar'].forEach(v => {
              this.$watch(v, () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            });
          },

          async abrirEditar(id) {
            this.aviso = 'Cargando colaborador…';
            try {
              const respuesta = await fetch(`/admin/colaboradores/${id}/detalle`, { headers: { 'Accept': 'application/json' } });
              if (respuesta.status === 410) { this.aviso = 'El colaborador ya no está disponible. Actualice el listado.'; return; }
              if (!respuesta.ok) throw new Error('detalle no disponible');
              this.seleccionado = await respuesta.json();
              this.editar = true;
              this.aviso = '';
            } catch (error) {
              this.aviso = 'No se pudo cargar el colaborador.';
            }
          },
          abrirEliminar(id, nombre) {
            this.seleccionado = { id, nombre, logo: '' };
            this.eliminar = true;
          },
        };
      }
    </script>
</x-admin-layout>
