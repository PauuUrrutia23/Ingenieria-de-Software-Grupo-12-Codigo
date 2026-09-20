<x-admin-layout>
    <x-slot name="header">Colaboradores</x-slot>

    <div x-data="moduloColaboradores()">

        <div class="flex items-center justify-between mb-8 -mt-2">
            <p class="text-slate-500 text-sm">Empresas y marcas asociadas a los proyectos de Ingecon.</p>
            <button type="button" @click="crear = true"
                    class="flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Proveedor
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

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5" role="list" aria-label="Listado de colaboradores">
            @forelse($colaboradores as $c)
            <article class="relative group bg-white rounded-xl p-5 shadow-sm border border-slate-100 text-center hover:shadow-md transition-shadow" role="listitem">
                <button type="button"
                        @click="abrirEliminar({{ $c->id_colaborador }}, @js($c->nombre_comercial))"
                        aria-label="Eliminar {{ $c->nombre_comercial }}"
                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/90 text-slate-400 hover:text-red-600 hover:bg-red-50 border border-slate-200 flex items-center justify-center opacity-0 group-hover:opacity-100 focus:opacity-100 transition-all">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>

                <button type="button"
                        @click="abrirEditar({{ $c->id_colaborador }}, @js($c->nombre_comercial), @js(Storage::url($c->logotipo)))"
                        class="w-full">
                    <div class="w-24 h-24 mx-auto mb-3 flex items-center justify-center rounded-lg bg-slate-50 overflow-hidden">
                        <img src="{{ Storage::url($c->logotipo) }}" alt="{{ $c->nombre_comercial }}" class="w-full h-full object-contain">
                    </div>
                    <p class="text-slate-900 font-semibold text-sm leading-snug line-clamp-2" title="{{ $c->nombre_comercial }}">{{ $c->nombre_comercial }}</p>
                    <p class="text-xs text-slate-400 mt-1 group-hover:text-slate-600 transition-colors">Editar</p>
                </button>
            </article>
            @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <i data-lucide="handshake" class="w-14 h-14 text-slate-300 mb-4"></i>
                <p class="text-slate-500 font-medium mb-1">Aún no hay registros</p>
                <p class="text-slate-400 text-sm mb-5">Agrega el primer colaborador usando el botón superior.</p>
            </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $colaboradores->links() }}</div>

        <x-modal show="crear" titulo="Nuevo Proveedor" ancho="max-w-md">
            <form action="{{ route('admin.colaboradores.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div>
                    <label for="crear-nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre Comercial</label>
                    <input id="crear-nombre" type="text" name="nombre_comercial" value="{{ old('_modal') === 'crear' ? old('nombre_comercial') : '' }}"
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                </div>
                <div x-data="{ nombreArchivo: '', vistaPrevia: '' }">
                    <label for="crear-logo" class="block text-sm font-semibold text-slate-700 mb-1.5">Logotipo</label>
                    <label for="crear-logo" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50 rounded-xl p-6 cursor-pointer transition-colors text-center">
                        <span x-show="!vistaPrevia"><i data-lucide="upload" class="w-7 h-7 text-slate-400"></i></span>
                        <img x-show="vistaPrevia" style="display:none;" :src="vistaPrevia" alt=""
                             class="h-16 max-w-full object-contain">
                        <span class="text-sm" :class="nombreArchivo ? 'text-slate-700 font-medium' : 'text-slate-500'"
                              x-text="nombreArchivo || 'Haz clic para seleccionar el logotipo'"></span>
                        <span class="text-xs text-slate-400" x-text="nombreArchivo ? 'Haz clic para elegir otro' : 'Máximo 500 KB (RNF17)'"></span>
                    </label>
                    <input id="crear-logo" type="file" name="logotipo" accept="image/*" class="sr-only" required
                           @change="nombreArchivo = $event.target.files[0] ? $event.target.files[0].name : '';
                                    vistaPrevia = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : ''">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="crear = false"
                            class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Guardar</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="editar" titulo="Editar Proveedor" ancho="max-w-md">
            <form :action="'{{ url('admin/colaboradores') }}/' + seleccionado.id" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="editar">
                <div>
                    <label for="editar-nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre Comercial</label>
                    <input id="editar-nombre" type="text" name="nombre_comercial" x-model="seleccionado.nombre"
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                </div>
                <div>
                    <span class="block text-sm font-semibold text-slate-700 mb-1.5">Logotipo actual</span>
                    <img :src="seleccionado.logo" alt="" class="h-14 object-contain border border-slate-200 rounded-lg p-1 mb-3 bg-slate-50">
                    <label for="editar-logo" class="block text-sm font-semibold text-slate-700 mb-1.5">Reemplazar logotipo (opcional)</label>
                    <input id="editar-logo" type="file" name="logotipo" accept="image/*"
                           class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <p class="text-xs text-slate-400 mt-1.5">Máximo 500 KB. Si no adjuntas nada, se conserva el actual.</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="editar = false"
                            class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Guardar cambios</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="eliminar" titulo="Eliminar Proveedor" ancho="max-w-md">
            <p class="text-slate-700 mb-6">
                ¿Confirma que desea eliminar a <strong x-text="seleccionado.nombre"></strong>?
                Esta acción no se puede deshacer.
            </p>
            <form :action="'{{ url('admin/colaboradores') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" @click="eliminar = false"
                        class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Sí, eliminar</button>
            </form>
        </x-modal>
    </div>

    <script>
      function moduloColaboradores() {
        return {
          crear: false,
          editar: false,
          eliminar: false,
          seleccionado: { id: null, nombre: '', logo: '' },

          init() {
            @if($errors->any() && old('_modal') === 'crear')
              this.crear = true;
            @endif
            ['crear', 'editar', 'eliminar'].forEach(v => {
              this.$watch(v, () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            });
          },

          abrirEditar(id, nombre, logo) {
            this.seleccionado = { id, nombre, logo };
            this.editar = true;
          },
          abrirEliminar(id, nombre) {
            this.seleccionado = { id, nombre, logo: '' };
            this.eliminar = true;
          },
        };
      }
    </script>
</x-admin-layout>
