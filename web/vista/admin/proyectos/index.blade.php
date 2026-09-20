<x-admin-layout>
    <x-slot name="header">Gestión de Proyectos</x-slot>

    <div x-data="moduloProyectos()">

        <div class="flex items-center justify-between mb-8 -mt-2">
            <p class="text-slate-500 text-sm">Portafolio de obras de Ingecon.</p>
            <button type="button" @click="crear = true"
                    class="flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nuevo Proyecto
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" role="list">
            @forelse($proyectos as $p)
            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-shadow" role="listitem">
                <div class="w-full h-40 bg-slate-100 overflow-hidden relative">
                    @if($p->imagenes->isNotEmpty())
                        <img src="{{ Storage::url($p->imagenes->first()->imagen) }}" alt="{{ $p->nombre_obra }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-slate-300">
                            <i data-lucide="image" class="w-10 h-10"></i>
                        </div>
                    @endif
                    <span class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ $p->imagenes->count() }} foto{{ $p->imagenes->count() !== 1 ? 's' : '' }}
                    </span>
                </div>

                <div class="p-5">
                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                        <form action="{{ route('admin.proyectos.visibilidad', $p) }}" method="POST">
                            @csrf @method('PATCH')
                            <select name="estado_publicacion" onchange="this.form.submit()"
                                    aria-label="Visibilidad de {{ $p->nombre_obra }}"
                                    class="text-xs font-semibold rounded-full border-0 pl-2.5 pr-7 py-0.5 cursor-pointer focus:ring-2 focus:ring-slate-300 outline-none appearance-none bg-no-repeat
                                    {{ $p->estado_publicacion === 'publicado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}"
                                    style="background-image:url('data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22currentColor%22%3E%3Cpath fill-rule=%22evenodd%22 d=%22M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z%22 clip-rule=%22evenodd%22/%3E%3C/svg%3E'); background-position: right 0.4rem center; background-size: 1em;">
                                <option value="borrador" {{ $p->estado_publicacion === 'borrador' ? 'selected' : '' }}>Borrador</option>
                                <option value="publicado" {{ $p->estado_publicacion === 'publicado' ? 'selected' : '' }}>Publicado</option>
                            </select>
                        </form>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $p->categoria }}</span>
                    </div>

                    <h3 class="font-bold text-slate-900 text-sm leading-snug mb-1 line-clamp-2">{{ $p->nombre_obra }}</h3>
                    <p class="text-slate-400 text-xs mb-4 flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                        {{ $p->ubicacion_geografica }} · {{ $p->anio_ejecucion }}
                    </p>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.proyectos.edit', $p) }}"
                           class="flex-1 flex items-center justify-center gap-2 border border-slate-200 hover:border-slate-400 hover:bg-slate-50 text-slate-700 text-sm font-medium py-2 rounded-lg transition-colors">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                            Editar
                        </a>
                        <button type="button"
                                @click="abrirEliminar({{ $p->id_proyecto }}, @js($p->nombre_obra), {{ $p->imagenes->count() }})"
                                aria-label="Eliminar {{ $p->nombre_obra }}"
                                class="flex items-center justify-center w-10 border border-slate-200 hover:border-red-300 hover:bg-red-50 text-slate-500 hover:text-red-600 rounded-lg transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </article>
            @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <i data-lucide="hard-hat" class="w-14 h-14 text-slate-300 mb-4"></i>
                <p class="text-slate-500 font-medium mb-1">Aún no hay registros</p>
                <p class="text-slate-400 text-sm mb-5">Crea tu primer proyecto usando el botón superior.</p>
            </div>
            @endforelse
        </div>

        <div class="mt-8">{{ $proyectos->links() }}</div>

        <x-modal show="crear" titulo="Nuevo Proyecto" ancho="max-w-2xl">
            <form action="{{ route('admin.proyectos.store') }}" method="POST" enctype="multipart/form-data"
                  class="space-y-5" @submit="validarImagenes($event)">
                @csrf
                <input type="hidden" name="_modal" value="crear">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="pr-nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de Obra <span class="text-red-500">*</span></label>
                        <input id="pr-nombre" type="text" name="nombre_obra" value="{{ old('nombre_obra') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                    </div>
                    <div>
                        <label for="pr-categoria" class="block text-sm font-semibold text-slate-700 mb-1.5">Categoría</label>
                        <select id="pr-categoria" name="categoria" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer" required>
                            <option value="Pabellones y galpones">Pabellones y galpones</option>
                            <option value="Vivienda industrializada">Vivienda industrializada</option>
                            <option value="Terminaciones y servicios">Terminaciones y servicios</option>
                        </select>
                    </div>
                    <div>
                        <label for="pr-anio" class="block text-sm font-semibold text-slate-700 mb-1.5">Año de Ejecución</label>
                        <input id="pr-anio" type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', date('Y')) }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                    </div>
                    <div>
                        <label for="pr-region" class="block text-sm font-semibold text-slate-700 mb-1.5">Región</label>
                        <input id="pr-region" type="text" name="region" value="{{ old('region') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                    </div>
                    <div>
                        <label for="pr-ubicacion" class="block text-sm font-semibold text-slate-700 mb-1.5">Ubicación / Comuna</label>
                        <input id="pr-ubicacion" type="text" name="ubicacion_geografica" value="{{ old('ubicacion_geografica') }}"
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="pr-estado" class="block text-sm font-semibold text-slate-700 mb-1.5">Estado inicial</label>
                        <select id="pr-estado" name="estado_publicacion" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer" required>
                            <option value="borrador">Borrador</option>
                            <option value="publicado">Publicado</option>
                        </select>
                        <p class="text-xs text-slate-400 mt-1.5">RF48: los proyectos se registran en Borrador; puedes publicarlos después desde el listado.</p>
                    </div>
                </div>

                <div>
                    <label for="pr-desc" class="block text-sm font-semibold text-slate-700 mb-1.5">Descripción Técnica</label>
                    <textarea id="pr-desc" name="descripcion_tecnica" rows="3"
                              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 resize-none" required>{{ old('descripcion_tecnica') }}</textarea>
                </div>

                <div>
                    <label for="pr-imagenes" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Fotografías de la obra <span class="font-normal text-slate-400">(máx. 15, hasta 5 MB c/u)</span>
                    </label>
                    <label for="pr-imagenes" class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50 rounded-xl p-6 cursor-pointer transition-colors text-center">
                        <i data-lucide="upload" class="w-8 h-8 text-slate-400"></i>
                        <span class="text-sm" :class="resumenImagenes ? 'text-slate-700 font-medium' : 'text-slate-500'"
                              x-text="resumenImagenes || 'Haz clic para seleccionar imágenes'"></span>
                        <span class="text-xs text-slate-400" x-text="resumenImagenes ? 'Haz clic para elegir otras' : 'JPG, PNG, WebP'"></span>
                    </label>
                    <input id="pr-imagenes" type="file" name="imagenes[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only"
                           @change="resumirImagenes()">
                    <p x-show="errorImagenes" style="display:none;" x-text="errorImagenes" class="text-sm text-red-600 font-medium mt-2"></p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="crear = false"
                            class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Guardar Proyecto</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="eliminar" titulo="Eliminar Proyecto" ancho="max-w-md">
            <p class="text-slate-700 mb-2">
                ¿Confirma que desea eliminar <strong x-text="seleccionado.nombre"></strong>?
            </p>
            <p class="text-sm text-slate-400 mb-6">
                Se borrarán permanentemente el registro y
                <strong x-text="seleccionado.imagenes"></strong> imagen(es) asociada(s). No se puede deshacer.
            </p>
            <form :action="'{{ url('admin/proyectos') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" @click="eliminar = false"
                        class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Sí, eliminar</button>
            </form>
        </x-modal>
    </div>

    <script>
      function moduloProyectos() {
        return {
          crear: false,
          eliminar: false,
          errorImagenes: '',
          resumenImagenes: '',
          seleccionado: { id: null, nombre: '', imagenes: 0 },

          init() {
            @if($errors->any() && old('_modal') === 'crear')
              this.crear = true;
            @endif
            this.$watch('crear', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            this.$watch('eliminar', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
          },

          resumirImagenes() {
            const archivos = [...(document.getElementById('pr-imagenes').files || [])];

            if (archivos.length === 0) {
              this.resumenImagenes = '';
            } else if (archivos.length === 1) {
              this.resumenImagenes = archivos[0].name;
            } else {
              this.resumenImagenes = archivos.length + ' fotografías seleccionadas';
            }
          },

          validarImagenes(e) {
            const input = document.getElementById('pr-imagenes');
            const archivos = [...(input.files || [])];
            this.errorImagenes = '';

            if (archivos.length > 15) {
              this.errorImagenes = 'Se permiten como máximo 15 fotografías por obra.';
            } else {
              const pesada = archivos.find(f => f.size > 5 * 1024 * 1024);
              if (pesada) {
                this.errorImagenes = `"${pesada.name}" supera los 5 MB permitidos por imagen.`;
              }
            }

            if (this.errorImagenes) e.preventDefault();
          },

          abrirEliminar(id, nombre, imagenes) {
            this.seleccionado = { id, nombre, imagenes };
            this.eliminar = true;
          },
        };
      }
    </script>
</x-admin-layout>
