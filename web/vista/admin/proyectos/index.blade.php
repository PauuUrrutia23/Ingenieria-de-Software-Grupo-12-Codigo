<x-admin-layout>
    <x-slot name="header">Gestión de Proyectos</x-slot>

    <div x-data="moduloProyectos()">

        <div class="flex flex-wrap items-end justify-between gap-4 pb-6">
            <div>
                <p class="ig-eyebrow mb-2">Portafolio</p>
                <p class="ig-lede max-w-xl">Obras de Ingecon. Ajusta visibilidad, edita o elimina cada registro.</p>
            </div>
            <button type="button" @click="crear = true" class="ig-btn ig-btn-primary shrink-0">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Nuevo Proyecto
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
        <p x-show="errorEdicion && !editar" x-text="errorEdicion" class="mb-6 ig-error" role="alert" style="display:none;"></p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" role="list">
            @forelse($proyectos as $p)
            <article class="ig-card ig-card-hover overflow-hidden flex flex-col" role="listitem">
                <div class="relative h-40 bg-paper-deep overflow-hidden">
                    @if($p->imagenes->isNotEmpty())
                        <img src="{{ Storage::url($p->imagenes->first()->imagen) }}" alt="{{ $p->nombre_obra }}" class="w-full h-full object-cover">
                    @else
                        <div class="ig-plate">
                            <span class="ig-plate-label">Sin fotografía</span>
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 font-mono text-[0.625rem] uppercase tracking-[0.14em] bg-carbon text-white px-2.5 py-1">
                        {{ $p->imagenes->count() }} foto{{ $p->imagenes->count() !== 1 ? 's' : '' }}
                    </span>
                </div>

                <div class="flex flex-col flex-1 p-5">
                    <div class="flex items-center gap-2 mb-3 flex-wrap">
                        <form action="{{ route('admin.proyectos.visibilidad', $p) }}" method="POST" class="leading-none">
                            @csrf @method('PATCH')
                            <select name="estado_publicacion" onchange="this.form.submit()"
                                    aria-label="Visibilidad de {{ $p->nombre_obra }}"
                                    class="ig-badge cursor-pointer appearance-none border-0 pr-7 py-1 pl-3 align-middle focus:outline-none
                                    {{ $p->estado_publicacion === 'publicado' ? 'ig-badge-ok' : 'ig-badge-warn' }}"
                                    style="background-image:url('data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 20 20%22 fill=%22currentColor%22%3E%3Cpath fill-rule=%22evenodd%22 d=%22M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z%22 clip-rule=%22evenodd%22/%3E%3C/svg%3E'); background-position: right 0.4rem center; background-size: 1em; background-repeat: no-repeat;">
                                <option value="borrador" {{ $p->estado_publicacion === 'borrador' ? 'selected' : '' }}>Borrador</option>
                                <option value="publicado" {{ $p->estado_publicacion === 'publicado' ? 'selected' : '' }}>Publicado</option>
                            </select>
                        </form>
                        <span class="ig-badge ig-badge-mute">{{ \App\Support\CategoriasProyecto::etiqueta($p->categoria) }}</span>
                    </div>

                    <h3 class="font-display text-lg leading-tight text-carbon mb-1 line-clamp-2">{{ $p->nombre_obra }}</h3>
                    <p class="ig-meta mb-5 flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="h-3.5 w-3.5 text-wood-deep"></i>
                        {{ $p->comuna }}, {{ $p->region }} · {{ $p->anio_ejecucion }}
                    </p>

                    <div class="mt-auto flex gap-2">
                        <button type="button" @click="abrirEditar({{ $p->id_proyecto }})"
                           class="ig-btn ig-btn-secondary flex-1 !px-4 !py-2">
                            <i data-lucide="pencil" class="h-4 w-4"></i>
                            Editar Info
                        </button>
                        <button type="button"
                                @click="abrirEliminar({{ $p->id_proyecto }}, @js($p->nombre_obra), {{ $p->imagenes->count() }})"
                                aria-label="Eliminar {{ $p->nombre_obra }}"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center border border-line-strong bg-surface text-mute-deep transition-colors hover:border-[#b4403f] hover:text-[#8c2f2f]">
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            </article>
            @empty
            <div class="col-span-full flex flex-col items-center justify-center py-20 text-center">
                <i data-lucide="hard-hat" class="h-14 w-14 text-line-strong mb-4"></i>
                <p class="text-carbon font-medium mb-1">Aún no hay registros</p>
                <p class="text-mute text-sm mb-5">Crea tu primer proyecto usando el botón superior.</p>
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
                        <label for="pr-nombre" class="ig-label">Nombre de Obra <span class="text-[#b4403f]">*</span></label>
                        <input id="pr-nombre" type="text" name="nombre_obra" value="{{ old('nombre_obra') }}"
                               class="ig-field-box" required>
                    </div>
                    <div>
                        <label for="pr-categoria" class="ig-label">Categoría</label>
                        <select id="pr-categoria" name="categoria" class="ig-field-box cursor-pointer" required>
                            @foreach(\App\Support\CategoriasProyecto::ETIQUETAS as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(old('categoria') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="pr-anio" class="ig-label">Año de Ejecución</label>
                        <input id="pr-anio" type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', date('Y')) }}"
                               class="ig-field-box" required>
                    </div>
                    <div>
                        <label for="pr-region" class="ig-label">Región</label>
                        <select id="pr-region" name="region" class="ig-field-box cursor-pointer" required><option value="">Seleccione una región…</option>@foreach(\App\Support\RegionesChile::LISTA as $r)<option value="{{ $r }}" {{ old('region') === $r ? 'selected' : '' }}>{{ $r }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label for="pr-ubicacion" class="ig-label">Comuna</label>
                        <input id="pr-ubicacion" type="text" name="comuna" value="{{ old('comuna') }}"
                               class="ig-field-box" required>
                    </div>
                    <div><label for="pr-latitud" class="ig-label">Latitud (opcional)</label><input id="pr-latitud" type="number" step="any" min="-90" max="90" name="latitud" value="{{ old('latitud') }}" class="ig-field-box"></div>
                    <div><label for="pr-longitud" class="ig-label">Longitud (opcional)</label><input id="pr-longitud" type="number" step="any" min="-180" max="180" name="longitud" value="{{ old('longitud') }}" class="ig-field-box"></div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-mute mt-1.5">RF48: El proyecto se creará como <strong>Borrador</strong>; podrás publicarlo después desde el listado.</p>
                    </div>
                </div>

                <div>
                    <label for="pr-desc" class="ig-label">Descripción Técnica</label>
                    <textarea id="pr-desc" name="descripcion_tecnica" rows="3"
                              class="ig-field-box resize-y" required>{{ old('descripcion_tecnica') }}</textarea>
                </div>

                <div>
                    <label for="pr-imagenes" class="ig-label">
                        Fotografías de la obra <span class="normal-case tracking-normal text-mute">(máx. 15, hasta 2 MB c/u)</span>
                    </label>
                    <label for="pr-imagenes" class="flex flex-col items-center justify-center gap-2 border border-dashed border-line-strong bg-paper p-6 cursor-pointer transition-colors hover:border-wood text-center">
                        <i data-lucide="upload" class="h-7 w-7 text-mute"></i>
                        <span class="text-sm text-carbon" :class="resumenImagenes ? 'font-medium' : ''"
                              x-text="resumenImagenes || 'Haz clic para seleccionar imágenes'"></span>
                        <span class="text-xs text-mute" x-text="resumenImagenes ? 'Haz clic para elegir otras' : 'JPG, PNG'"></span>
                    </label>
                    <input id="pr-imagenes" type="file" name="imagenes[]" multiple accept="image/jpeg,image/png" class="sr-only"
                           @change="resumirImagenes()">
                    <p x-show="errorImagenes" style="display:none;" x-text="errorImagenes" class="ig-error"></p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="crear = false"
                            class="ig-btn ig-btn-secondary">Cancelar</button>
                    <button type="submit"
                            class="ig-btn ig-btn-primary">Guardar Proyecto</button>
                </div>
            </form>
        </x-modal>

        <x-modal show="editar" titulo="Editar Info del Proyecto" ancho="max-w-2xl">
            <form :action="'{{ url('admin/proyectos') }}/' + editado.id" method="POST" enctype="multipart/form-data" class="space-y-5" @submit="validarImagenesEdicion($event)">
                @csrf @method('PUT')
                <input type="hidden" name="_modal" value="editar">
                <input type="hidden" name="_proyecto_id" :value="editado.id">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2"><label for="editar-proyecto-nombre" class="ig-label">Nombre de Obra</label><input id="editar-proyecto-nombre" name="nombre_obra" x-model="editado.nombre_obra" class="ig-field-box" required></div>
                    <div><label for="editar-proyecto-categoria" class="ig-label">Categoría</label><select id="editar-proyecto-categoria" name="categoria" x-model="editado.categoria" class="ig-field-box" required>
                        @foreach(\App\Support\CategoriasProyecto::ETIQUETAS as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select></div>
                    <div><label for="editar-proyecto-anio" class="ig-label">Año de Ejecución</label><input id="editar-proyecto-anio" type="number" name="anio_ejecucion" x-model="editado.anio_ejecucion" class="ig-field-box" required></div>
                    <div><label for="editar-proyecto-region" class="ig-label">Región</label><select id="editar-proyecto-region" name="region" x-model="editado.region" class="ig-field-box cursor-pointer" required><option value="">Seleccione una región…</option>@foreach(\App\Support\RegionesChile::LISTA as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select></div>
                    <div><label for="editar-proyecto-ubicacion" class="ig-label">Comuna</label><input id="editar-proyecto-ubicacion" name="comuna" x-model="editado.comuna" class="ig-field-box" required></div>
                    <div><label for="editar-proyecto-latitud" class="ig-label">Latitud (opcional)</label><input id="editar-proyecto-latitud" type="number" step="any" min="-90" max="90" name="latitud" x-model="editado.latitud" class="ig-field-box"></div>
                    <div><label for="editar-proyecto-longitud" class="ig-label">Longitud (opcional)</label><input id="editar-proyecto-longitud" type="number" step="any" min="-180" max="180" name="longitud" x-model="editado.longitud" class="ig-field-box"></div>
                    <div class="sm:col-span-2"><label for="editar-proyecto-estado" class="ig-label">Visibilidad</label><select id="editar-proyecto-estado" name="estado_publicacion" x-model="editado.estado_publicacion" class="ig-field-box"><option value="borrador">Borrador</option><option value="publicado">Publicado</option></select></div>
                    <div class="sm:col-span-2"><label for="editar-proyecto-desc" class="ig-label">Descripción Técnica</label><textarea id="editar-proyecto-desc" name="descripcion_tecnica" rows="3" x-model="editado.descripcion_tecnica" class="ig-field-box" required></textarea></div>
                    <div class="sm:col-span-2"><label for="editar-proyecto-imagenes" class="ig-label">Agregar fotografías (máximo 15 en total, 2 MB cada una)</label><input id="editar-proyecto-imagenes" type="file" name="imagenes[]" multiple accept="image/jpeg,image/png" class="ig-field-box"></div>
                </div>
                <p class="text-xs text-mute" x-text="`${editado.imagenes_count || 0} fotografía(s) existentes. Para gestionar fotografías individuales, use la página de edición completa.`"></p>
                <p x-show="errorEdicion" x-text="errorEdicion" class="ig-error" role="alert"></p>
                <div class="flex justify-between gap-3 pt-2"><a :href="'{{ url('admin/proyectos') }}/' + editado.id + '/edit'" class="ig-btn ig-btn-secondary">Gestionar fotografías</a><div class="flex gap-3"><button type="button" @click="editar = false" class="ig-btn ig-btn-secondary">Cancelar</button><button type="submit" class="ig-btn ig-btn-primary">Guardar cambios</button></div></div>
            </form>
        </x-modal>

        <x-modal show="eliminar" titulo="Eliminar Proyecto" ancho="max-w-md">
            <p class="text-carbon mb-2">
                ¿Confirma que desea eliminar <strong x-text="seleccionado.nombre"></strong>?
            </p>
            <p class="text-sm text-mute mb-6">
                Se borrarán permanentemente el registro y
                <strong class="text-mute-deep" x-text="seleccionado.imagenes"></strong> imagen(es) asociada(s). No se puede deshacer.
            </p>
            <form :action="'{{ url('admin/proyectos') }}/' + seleccionado.id" method="POST" class="flex justify-end gap-3">
                @csrf @method('DELETE')
                <button type="button" @click="eliminar = false"
                        class="ig-btn ig-btn-secondary">Cancelar</button>
                <button type="submit"
                        class="ig-btn border border-[#b4403f] bg-[#8c2f2f] text-white hover:bg-[#7a2828] hover:border-[#7a2828]">Sí, eliminar</button>
            </form>
        </x-modal>
    </div>

    <script>
      function moduloProyectos() {
        return {
          crear: false,
          editar: false,
          eliminar: false,
          errorImagenes: '',
          errorEdicion: '',
          resumenImagenes: '',
          seleccionado: { id: null, nombre: '', imagenes: 0 },
          editado: { id: null, nombre_obra: '', categoria: 'construccion', anio_ejecucion: '', region: '', comuna: '', latitud: '', longitud: '', estado_publicacion: 'borrador', descripcion_tecnica: '', imagenes_count: 0 },

          init() {
            @if($errors->any() && old('_modal') === 'crear')
              this.crear = true;
            @endif
            @if($errors->any() && old('_modal') === 'editar')
              this.editado = {
                id: @js(old('_proyecto_id')), nombre_obra: @js(old('nombre_obra')),
                categoria: @js(old('categoria')), anio_ejecucion: @js(old('anio_ejecucion')),
                region: @js(old('region')), comuna: @js(old('comuna')),
                latitud: @js(old('latitud')), longitud: @js(old('longitud')),
                estado_publicacion: @js(old('estado_publicacion')),
                descripcion_tecnica: @js(old('descripcion_tecnica')), imagenes_count: 0,
              };
              this.editar = true;
            @endif
            this.$watch('crear', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            this.$watch('editar', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
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
              const pesada = archivos.find(f => f.size > 2 * 1024 * 1024);
              if (pesada) {
                this.errorImagenes = `"${pesada.name}" supera los 2 MB permitidos por imagen.`;
              }
            }

            if (this.errorImagenes) e.preventDefault();
          },

          abrirEliminar(id, nombre, imagenes) {
            this.seleccionado = { id, nombre, imagenes };
            this.eliminar = true;
          },

          async abrirEditar(id) {
            this.errorEdicion = '';
            try {
              const respuesta = await fetch(`{{ url('admin/proyectos') }}/${id}/detalle-edicion`, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
              });
              const datos = await respuesta.json();
              if (!respuesta.ok) {
                this.errorEdicion = datos.message || 'No se pudo cargar el proyecto. Actualice el listado.';
                if (respuesta.status === 404) window.setTimeout(() => window.location.reload(), 1800);
                return;
              }
              this.editado = { ...datos, id: datos.id_proyecto };
              this.editar = true;
            } catch (_) {
              this.errorEdicion = 'No se pudo cargar el proyecto. Intente nuevamente.';
            }
          },

          validarImagenesEdicion(evento) {
            this.errorEdicion = '';
            const archivos = [...(document.getElementById('editar-proyecto-imagenes')?.files || [])];
            if (archivos.length + (this.editado.imagenes_count || 0) > 15) {
              this.errorEdicion = 'El proyecto no puede superar 15 fotografías en total.';
            } else if (archivos.some(archivo => archivo.size > 2 * 1024 * 1024)) {
              this.errorEdicion = 'Cada fotografía debe pesar como máximo 2 MB.';
            }
            if (this.errorEdicion) evento.preventDefault();
          },
        };
      }
    </script>
</x-admin-layout>
