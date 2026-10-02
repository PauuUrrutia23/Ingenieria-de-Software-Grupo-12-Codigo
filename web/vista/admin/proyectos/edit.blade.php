<x-admin-layout>
    <x-slot name="header">Editar Proyecto</x-slot>

    @if($errors->any())
        <div class="mb-6 border border-[#b4403f] bg-surface px-5 py-3 text-sm text-[#8c2f2f]">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.proyectos.update', $proyecto) }}" method="POST" enctype="multipart/form-data"
          class="ig-card max-w-3xl">
        @csrf @method('PUT')
        <div class="border-b border-line px-6 py-4 flex items-center justify-between gap-3">
            <p class="ig-eyebrow">Ficha de obra</p>
            <span class="ig-badge {{ $proyecto->estado_publicacion === 'publicado' ? 'ig-badge-ok' : 'ig-badge-warn' }}">
                {{ $proyecto->estado_publicacion }}
            </span>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="ed-nombre" class="ig-label">Nombre de Obra</label>
                    <input id="ed-nombre" type="text" name="nombre_obra" value="{{ old('nombre_obra', $proyecto->nombre_obra) }}"
                           class="ig-field-box" required>
                </div>
                <div>
                    <label for="ed-categoria" class="ig-label">Categoría</label>
                    <select id="ed-categoria" name="categoria" class="ig-field-box cursor-pointer" required>
                        <option value="Pabellones y galpones" {{ $proyecto->categoria == 'Pabellones y galpones' ? 'selected' : '' }}>Pabellones y galpones</option>
                        <option value="Vivienda industrializada" {{ $proyecto->categoria == 'Vivienda industrializada' ? 'selected' : '' }}>Vivienda industrializada</option>
                        <option value="Terminaciones y servicios" {{ $proyecto->categoria == 'Terminaciones y servicios' ? 'selected' : '' }}>Terminaciones y servicios</option>
                    </select>
                </div>
                <div>
                    <label for="ed-anio" class="ig-label">Año de Ejecución</label>
                    <input id="ed-anio" type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', $proyecto->anio_ejecucion) }}"
                           class="ig-field-box" required>
                </div>
                <div>
                    <label for="ed-region" class="ig-label">Región</label>
                    <input id="ed-region" type="text" name="region" value="{{ old('region', $proyecto->region) }}"
                           class="ig-field-box" required>
                </div>
                <div>
                    <label for="ed-ubicacion" class="ig-label">Ubicación / Comuna</label>
                    <input id="ed-ubicacion" type="text" name="ubicacion_geografica" value="{{ old('ubicacion_geografica', $proyecto->ubicacion_geografica) }}"
                           class="ig-field-box" required>
                </div>
                <div class="sm:col-span-2">
                    <label for="ed-estado" class="ig-label">Estado de visibilidad</label>
                    <select id="ed-estado" name="estado_publicacion" class="ig-field-box cursor-pointer" required>
                        <option value="borrador" {{ $proyecto->estado_publicacion == 'borrador' ? 'selected' : '' }}>Borrador (oculto)</option>
                        <option value="publicado" {{ $proyecto->estado_publicacion == 'publicado' ? 'selected' : '' }}>Publicado (visible)</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="ed-desc" class="ig-label">Descripción Técnica</label>
                <textarea id="ed-desc" name="descripcion_tecnica" rows="4"
                          class="ig-field-box resize-y" required>{{ old('descripcion_tecnica', $proyecto->descripcion_tecnica) }}</textarea>
            </div>

            <div>
                <label for="ed-imagenes" class="ig-label">Agregar nuevas fotografías</label>
                <input id="ed-imagenes" type="file" name="imagenes[]" multiple accept="image/jpeg,image/png"
                       class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.16em] file:text-white hover:border-line-strong hover:file:bg-wood hover:file:text-carbon">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.proyectos.index') }}"
                   class="ig-btn ig-btn-secondary">Cancelar</a>
                <button type="submit"
                        class="ig-btn ig-btn-primary">Actualizar Proyecto</button>
            </div>
        </div>
    </form>

    <div class="ig-card max-w-3xl mt-6">
        <div class="border-b border-line px-6 py-4">
            <h3 class="font-display text-lg text-carbon">Imágenes Actuales</h3>
        </div>
        <div class="p-6">
            @if($proyecto->imagenes->isEmpty())
                <p class="text-sm text-mute">Este proyecto todavía no tiene fotografías.</p>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach($proyecto->imagenes as $img)
                    <div class="relative group overflow-hidden border border-line">
                        <img src="{{ Storage::url($img->imagen) }}" alt="" class="w-full h-28 object-cover">
                        <form action="{{ route('admin.imagenes.destroy', $img) }}" method="POST"
                              class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity"
                              onsubmit="return confirm('¿Eliminar esta imagen?')">
                            @csrf @method('DELETE')
                            <button type="submit" aria-label="Eliminar imagen"
                                    class="inline-flex h-7 w-7 items-center justify-center bg-carbon text-white transition-colors hover:bg-[#8c2f2f]">
                                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
