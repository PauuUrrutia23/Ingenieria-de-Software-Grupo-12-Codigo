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
                        <option value="Pabellones y galpones">Pabellones y galpones</option>
                        <option value="Vivienda industrializada">Vivienda industrializada</option>
                        <option value="Terminaciones y servicios">Terminaciones y servicios</option>
                    </select>
                </div>
                <div>
                    <label for="c-region" class="ig-label">Región</label>
                    <input id="c-region" type="text" name="region" value="{{ old('region') }}" class="ig-field-box" required>
                </div>
                <div>
                    <label for="c-ubicacion" class="ig-label">Ubicación / Comuna</label>
                    <input id="c-ubicacion" type="text" name="ubicacion_geografica" value="{{ old('ubicacion_geografica') }}" class="ig-field-box" required>
                </div>
                <div>
                    <label for="c-anio" class="ig-label">Año de Ejecución</label>
                    <input id="c-anio" type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', date('Y')) }}" class="ig-field-box" required>
                </div>
                <div class="sm:col-span-2">
                    <label for="c-estado" class="ig-label">Estado</label>
                    <select id="c-estado" name="estado_publicacion" class="ig-field-box cursor-pointer" required>
                        <option value="borrador">Borrador</option>
                        <option value="publicado">Publicado</option>
                        <option value="oculto">Oculto (opción legada)</option>
                    </select>
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
