<x-admin-layout>
    <x-slot name="header">Editar — {{ $nombresSeccion[$contenido->seccion] }}</x-slot>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-xl">
        <form action="{{ route('admin.contenido.update', $contenido) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Mismas etiquetas y obligatoriedad que en el alta (CU 43.6 a CU 43.9). --}}
            @php
                $etiquetaTitulo = [
                    'faq' => 'Pregunta',
                    'opiniones' => 'Nombre del cliente',
                    'fases_industriales' => 'Nombre de la fase',
                    'banner' => 'Título (opcional)',
                ][$contenido->seccion] ?? 'Título';
                $etiquetaCuerpo = [
                    'faq' => 'Respuesta',
                    'opiniones' => 'Testimonio',
                    'fases_industriales' => 'Texto de la fase',
                    'banner' => 'Texto descriptivo',
                ][$contenido->seccion] ?? 'Descripción';
                $tituloObligatorio = $contenido->seccion !== 'banner';
            @endphp

            <div>
                <label for="cont-titulo" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ $etiquetaTitulo }}</label>
                <input id="cont-titulo" type="text" name="titulo" value="{{ old('titulo', $contenido->titulo) }}" @if($tituloObligatorio) required @endif
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                @error('titulo') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cont-cuerpo" class="block text-sm font-semibold text-slate-700 mb-1.5">{{ $etiquetaCuerpo }}</label>
                <textarea id="cont-cuerpo" name="cuerpo" rows="4" required
                          class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 resize-none">{{ old('cuerpo', $contenido->cuerpo) }}</textarea>
                @error('cuerpo') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            @if($contenido->archivo)
                <div>
                    <p class="text-sm font-semibold text-slate-700 mb-1.5">Archivo actual</p>
                    <a href="{{ Storage::url($contenido->archivo) }}" target="_blank" class="text-sm text-slate-600 hover:text-slate-900 hover:underline">Ver archivo actual</a>
                </div>
            @endif

            <div>
                <label for="cont-archivo" class="block text-sm font-semibold text-slate-700 mb-1.5">Reemplazar imagen o video <span class="font-normal text-slate-400">(opcional)</span></label>
                <input id="cont-archivo" type="file" name="archivo" accept="image/jpeg,image/png,image/webp,video/mp4"
                       class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                <p class="text-xs text-slate-400 mt-1.5">JPG, PNG, WebP o MP4. Máximo 5 MB.</p>
                @error('archivo') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cont-enlace" class="block text-sm font-semibold text-slate-700 mb-1.5">Enlace <span class="font-normal text-slate-400">(opcional)</span></label>
                <input id="cont-enlace" type="url" name="enlace" value="{{ old('enlace', $contenido->enlace) }}" placeholder="https://"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                @error('enlace') <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cont-orden" class="block text-sm font-semibold text-slate-700 mb-1.5">Orden</label>
                <input id="cont-orden" type="number" name="orden" value="{{ old('orden', $contenido->orden) }}"
                       class="w-32 px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.contenido.index', ['seccion' => $contenido->seccion]) }}"
                   class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</a>
                <button type="submit"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Guardar cambios</button>
            </div>
        </form>
    </div>
</x-admin-layout>
