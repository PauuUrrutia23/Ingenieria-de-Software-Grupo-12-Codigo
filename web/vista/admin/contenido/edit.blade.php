<x-admin-layout>
    <x-slot name="header">Editar — {{ $nombresSeccion[$contenido->seccion] }}</x-slot>

    <div class="mb-8 -mt-2">
        <p class="ig-eyebrow mb-2">Panel de gestión · {{ $nombresSeccion[$contenido->seccion] }}</p>
        <p class="text-mute text-sm">Registro N° {{ $contenido->id_contenido }}. Los cambios se reflejan en el sitio público al guardar.</p>
    </div>

    <div class="ig-card max-w-xl">
        <div class="bg-paper-deep border-b border-line px-5 py-3">
            <h2 class="ig-eyebrow">Editar registro</h2>
        </div>
        <form action="{{ route('admin.contenido.update', $contenido) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-5">
            @csrf
            @method('PUT')

            @php
                $etiquetaTitulo = [
                    'faq' => 'Pregunta',
                    'opiniones' => 'Nombre del cliente',
                    'fases_industriales' => 'Nombre de la fase',
                    'banner' => 'Título (opcional)',
                    'ubicacion' => 'Descripción (opcional)',
                    'documentacion' => 'Descripción (opcional)',
                ][$contenido->seccion] ?? 'Título';
                $etiquetaCuerpo = [
                    'faq' => 'Respuesta',
                    'opiniones' => 'Testimonio',
                    'fases_industriales' => 'Texto de la fase',
                    'banner' => 'Texto descriptivo',
                    'ubicacion' => '',
                    'documentacion' => '',
                ][$contenido->seccion] ?? 'Descripción';
                $esEnlace = in_array($contenido->seccion, ['ubicacion', 'documentacion'], true);
                $tituloObligatorio = !$esEnlace && $contenido->seccion !== 'banner';
            @endphp

            <div>
                <label for="cont-titulo" class="ig-label">{{ $etiquetaTitulo }}@if($tituloObligatorio) <span class="text-wood-deep">*</span>@endif</label>
                <input id="cont-titulo" type="text" name="titulo" value="{{ old('titulo', $contenido->titulo) }}" @if($tituloObligatorio) required @endif
                       class="ig-field-box @error('titulo') ig-field-box-invalid @enderror">
                @error('titulo') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            @if($esEnlace)
            <div>
                <label for="cont-enlace" class="ig-label">Enlace <span class="text-wood-deep">*</span></label>
                <input id="cont-enlace" type="text" name="enlace" value="{{ old('enlace', $contenido->enlace) }}" required maxlength="300"
                       placeholder="{{ $contenido->seccion === 'ubicacion' ? 'https://maps.google.com/?q=...' : 'https://... o /docs/archivo.pdf' }}"
                       class="ig-field-box @error('enlace') ig-field-box-invalid @enderror">
                <p class="text-xs text-mute mt-2">{{ $contenido->seccion === 'ubicacion' ? 'Enlace de Google Maps que se abre desde el pie de página (RF03).' : 'Enlace de la documentación técnica de Conectores Metálicos (RF10).' }} Se abre en una nueva pestaña.</p>
                @error('enlace') <p class="ig-error">{{ $message }}</p> @enderror
            </div>
            @else
            <div>
                <label for="cont-cuerpo" class="ig-label">{{ $etiquetaCuerpo }} <span class="text-wood-deep">*</span></label>
                <textarea id="cont-cuerpo" name="cuerpo" rows="4" required
                          class="ig-field-box resize-none @error('cuerpo') ig-field-box-invalid @enderror">{{ old('cuerpo', $contenido->cuerpo) }}</textarea>
                @error('cuerpo') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            @if($contenido->archivo)
                <div class="border-t border-line pt-4">
                    <p class="ig-label">Archivo actual</p>
                    <a href="{{ Storage::url($contenido->archivo) }}" target="_blank" rel="noopener"
                       class="ig-link-ghost !normal-case !tracking-normal !text-sm">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        Ver archivo actual
                    </a>
                </div>
            @endif

            <div>
                <label for="cont-archivo" class="ig-label">Reemplazar imagen o video <span class="font-normal normal-case tracking-normal text-mute">(opcional)</span></label>
                <input id="cont-archivo" type="file" name="archivo" accept="image/jpeg,image/png,image/webp,video/mp4"
                       class="ig-field-box cursor-pointer @error('archivo') ig-field-box-invalid @enderror">
                <p class="text-xs text-mute mt-2">JPG, PNG, WebP o MP4. Máximo 5 MB. Si lo dejas vacío, se conserva el actual.</p>
                @error('archivo') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            @endif

            <div class="flex justify-end gap-3 pt-2 border-t border-line">
                <a href="{{ route('admin.contenido.index', ['seccion' => $contenido->seccion]) }}"
                   class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</a>
                <button type="submit"
                        class="ig-btn ig-btn-primary !py-2.5 !px-5">Guardar cambios</button>
            </div>
        </form>
    </div>
</x-admin-layout>