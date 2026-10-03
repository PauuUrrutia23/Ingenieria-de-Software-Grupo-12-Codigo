<x-admin-layout>
    <x-slot name="header">Nuevo Proyecto</x-slot>

    @if($errors->any())
        <div class="mb-6 border border-[#b4403f] bg-surface px-5 py-3 text-sm text-[#8c2f2f]">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.proyectos.store') }}" method="POST" enctype="multipart/form-data" class="ig-card max-w-3xl">
        @csrf
        <div class="border-b border-line px-6 py-4">
            <p class="ig-eyebrow">Registro de obra</p>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label for="c-nombre" class="ig-label">Nombre de Obra <span class="text-[#b4403f]">*</span></label>
                    <input id="c-nombre" type="text" name="nombre_obra" value="{{ old('nombre_obra') }}" class="ig-field-box" required>
                </div>
                <div>
                    <label for="c-categoria" class="ig-label">Categoría</label>
                    <select id="c-categoria" name="categoria" class="ig-field-box cursor-pointer" required>
                        @foreach(\App\Support\CategoriasProyecto::ETIQUETAS as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('categoria') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="c-region" class="ig-label">Región</label>
                    <select id="c-region" name="region" class="ig-field-box cursor-pointer" required><option value="">Seleccione una región…</option>@foreach(\App\Support\RegionesChile::LISTA as $r)<option value="{{ $r }}" {{ old('region') === $r ? 'selected' : '' }}>{{ $r }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="c-ubicacion" class="ig-label">Comuna</label>
                    <input id="c-ubicacion" type="text" name="comuna" value="{{ old('comuna') }}" class="ig-field-box" required>
                </div>
                <div>
                    <label for="c-latitud" class="ig-label">Latitud (opcional)</label>
                    <input id="c-latitud" type="number" step="any" min="-90" max="90" name="latitud" value="{{ old('latitud') }}" class="ig-field-box">
                </div>
                <div>
                    <label for="c-longitud" class="ig-label">Longitud (opcional)</label>
                    <input id="c-longitud" type="number" step="any" min="-180" max="180" name="longitud" value="{{ old('longitud') }}" class="ig-field-box">
                </div>
                <div>
                    <label for="c-anio" class="ig-label">Año de Ejecución</label>
                    <input id="c-anio" type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', date('Y')) }}" class="ig-field-box" required>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs text-mute mt-1.5">RF48: El proyecto se creará como <strong>Borrador</strong>; podrás publicarlo después desde el listado.</p>
                </div>
            </div>

            <div>
                <label for="c-desc" class="ig-label">Descripción Técnica</label>
                <textarea id="c-desc" name="descripcion_tecnica" rows="4" class="ig-field-box resize-y" required>{{ old('descripcion_tecnica') }}</textarea>
            </div>

            <div>
                <label for="c-imagenes" class="ig-label">Imágenes (Múltiples, max 2MB c/u)</label>
                <input id="c-imagenes" type="file" name="imagenes[]" multiple accept="image/jpeg,image/png"
                       class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong">
            </div>
        </div>

        <div class="border-t border-line px-6 py-4 flex items-center justify-end gap-3">
            <a href="{{ route('admin.proyectos.index') }}" class="ig-btn ig-btn-secondary">Cancelar</a>
            <button type="submit" class="ig-btn ig-btn-primary">Guardar Proyecto</button>
        </div>
    </form>
</x-admin-layout>
