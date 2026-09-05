<x-admin-layout>
    <x-slot name="header">Editar Proyecto</x-slot>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm px-5 py-3 rounded-xl">
            <ul class="list-disc list-inside">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.proyectos.update', $proyecto) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-3xl space-y-5">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="ed-nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de Obra</label>
                <input id="ed-nombre" type="text" name="nombre_obra" value="{{ old('nombre_obra', $proyecto->nombre_obra) }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ed-categoria" class="block text-sm font-semibold text-slate-700 mb-1.5">Categoría</label>
                <select id="ed-categoria" name="categoria" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer" required>
                    <option value="Pabellones y galpones" {{ $proyecto->categoria == 'Pabellones y galpones' ? 'selected' : '' }}>Pabellones y galpones</option>
                    <option value="Vivienda industrializada" {{ $proyecto->categoria == 'Vivienda industrializada' ? 'selected' : '' }}>Vivienda industrializada</option>
                    <option value="Terminaciones y servicios" {{ $proyecto->categoria == 'Terminaciones y servicios' ? 'selected' : '' }}>Terminaciones y servicios</option>
                </select>
            </div>
            <div>
                <label for="ed-anio" class="block text-sm font-semibold text-slate-700 mb-1.5">Año de Ejecución</label>
                <input id="ed-anio" type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', $proyecto->anio_ejecucion) }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ed-region" class="block text-sm font-semibold text-slate-700 mb-1.5">Región</label>
                <input id="ed-region" type="text" name="region" value="{{ old('region', $proyecto->region) }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ed-ubicacion" class="block text-sm font-semibold text-slate-700 mb-1.5">Ubicación / Comuna</label>
                <input id="ed-ubicacion" type="text" name="ubicacion_geografica" value="{{ old('ubicacion_geografica', $proyecto->ubicacion_geografica) }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div class="sm:col-span-2">
                <label for="ed-estado" class="block text-sm font-semibold text-slate-700 mb-1.5">Estado de visibilidad</label>
                <select id="ed-estado" name="estado_publicacion" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer" required>
                    <option value="borrador" {{ $proyecto->estado_publicacion == 'borrador' ? 'selected' : '' }}>Borrador (oculto)</option>
                    <option value="publicado" {{ $proyecto->estado_publicacion == 'publicado' ? 'selected' : '' }}>Publicado (visible)</option>
                </select>
            </div>
        </div>

        <div>
            <label for="ed-desc" class="block text-sm font-semibold text-slate-700 mb-1.5">Descripción Técnica</label>
            <textarea id="ed-desc" name="descripcion_tecnica" rows="4"
                      class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 resize-none" required>{{ old('descripcion_tecnica', $proyecto->descripcion_tecnica) }}</textarea>
        </div>

        <div>
            <label for="ed-imagenes" class="block text-sm font-semibold text-slate-700 mb-1.5">Agregar nuevas fotografías</label>
            <input id="ed-imagenes" type="file" name="imagenes[]" multiple accept="image/*"
                   class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.proyectos.index') }}"
               class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</a>
            <button type="submit"
                    class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Actualizar Proyecto</button>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-3xl mt-6">
        <h3 class="font-bold text-slate-900 mb-4">Imágenes Actuales</h3>
        @if($proyecto->imagenes->isEmpty())
            <p class="text-sm text-slate-400">Este proyecto todavía no tiene fotografías.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($proyecto->imagenes as $img)
                <div class="relative group rounded-lg overflow-hidden border border-slate-100">
                    <img src="{{ Storage::url($img->imagen) }}" alt="" class="w-full h-28 object-cover">
                    <form action="{{ route('admin.imagenes.destroy', $img) }}" method="POST"
                          class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
                          onsubmit="return confirm('¿Eliminar esta imagen?')">
                        @csrf @method('DELETE')
                        <button type="submit" aria-label="Eliminar imagen"
                                class="w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition-colors">
                            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
