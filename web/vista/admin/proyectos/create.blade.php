<x-admin-layout>
    <x-slot name="header">Nuevo Proyecto</x-slot>

    @if($errors->any())
        <div class="bg-red-50 text-red-600 p-4 rounded mb-4"><ul>@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul></div>
    @endif

    <form action="{{ route('admin.proyectos.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow max-w-4xl space-y-6">
        @csrf
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold mb-2">Nombre de Obra</label>
                <input type="text" name="nombre_obra" value="{{ old('nombre_obra') }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Categoría</label>
                <select name="categoria" class="w-full border rounded p-2" required>
                    <option value="Pabellones y galpones">Pabellones y galpones</option>
                    <option value="Vivienda industrializada">Vivienda industrializada</option>
                    <option value="Terminaciones y servicios">Terminaciones y servicios</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Región</label>
                <input type="text" name="region" value="{{ old('region') }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Ubicación / Comuna</label>
                <input type="text" name="ubicacion_geografica" value="{{ old('ubicacion_geografica') }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Año de Ejecución</label>
                <input type="number" name="anio_ejecucion" value="{{ old('anio_ejecucion', date('Y')) }}" class="w-full border rounded p-2" required>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">Estado</label>
                <select name="estado_publicacion" class="w-full border rounded p-2" required>
                    <option value="borrador">Borrador</option>
                    <option value="publicado">Publicado</option>
                    <option value="oculto">Oculto</option>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-bold mb-2">Descripción Técnica</label>
            <textarea name="descripcion_tecnica" rows="4" class="w-full border rounded p-2" required>{{ old('descripcion_tecnica') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-bold mb-2">Imágenes (Múltiples, max 2MB c/u)</label>
            <input type="file" name="imagenes[]" multiple accept="image/*" class="w-full border rounded p-2">
        </div>

        <button type="submit" class="bg-[#28533c] text-white px-6 py-2 rounded font-bold">Guardar Proyecto</button>
    </form>
</x-admin-layout>