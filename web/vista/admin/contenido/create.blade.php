<x-admin-layout>
    <x-slot name="header">Agregar a {{ $nombresSeccion[$seccion] }}</x-slot>

    <div class="mb-8 -mt-2">
        <p class="ig-eyebrow mb-2">Panel de gestión · {{ $nombresSeccion[$seccion] }}</p>
        <p class="text-mute text-sm">El contenido se publica en el sitio público según la sección elegida.</p>
    </div>

    <div class="ig-card max-w-xl">
        <div class="bg-paper-deep border-b border-line px-5 py-3">
            <h2 class="ig-eyebrow">Nuevo registro</h2>
        </div>
        <form action="{{ route('admin.contenido.store') }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-5">
            @csrf
            <input type="hidden" name="seccion" value="{{ $seccion }}">

            @php
                $etiquetaTitulo = [
                    'faq' => 'Pregunta',
                    'opiniones' => 'Nombre del cliente',
                    'fases_industriales' => 'Nombre de la fase',
                    'banner' => 'Título (opcional)',
                ][$seccion];
                $etiquetaCuerpo = [
                    'faq' => 'Respuesta',
                    'opiniones' => 'Testimonio',
                    'fases_industriales' => 'Texto de la fase',
                    'banner' => 'Texto descriptivo',
                ][$seccion];
                $tituloObligatorio = $seccion !== 'banner';
            @endphp

            <div>
                <label for="cont-titulo" class="ig-label">{{ $etiquetaTitulo }}@if($tituloObligatorio) <span class="text-wood-deep">*</span>@endif</label>
                <input id="cont-titulo" type="text" name="titulo" value="{{ old('titulo') }}" @if($tituloObligatorio) required @endif
                       class="ig-field-box @error('titulo') ig-field-box-invalid @enderror">
                @error('titulo') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cont-cuerpo" class="ig-label">{{ $etiquetaCuerpo }} <span class="text-wood-deep">*</span></label>
                <textarea id="cont-cuerpo" name="cuerpo" rows="4" required
                          class="ig-field-box resize-none @error('cuerpo') ig-field-box-invalid @enderror">{{ old('cuerpo') }}</textarea>
                <p class="text-xs text-mute mt-2">Se muestra tal cual en el sitio público. Máximo un párrafo por registro.</p>
                @error('cuerpo') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cont-archivo" class="ig-label">Imagen o video <span class="font-normal normal-case tracking-normal text-mute">(opcional)</span></label>
                <input id="cont-archivo" type="file" name="archivo" accept="image/jpeg,image/png,image/webp,video/mp4"
                       class="ig-field-box cursor-pointer @error('archivo') ig-field-box-invalid @enderror">
                <p class="text-xs text-mute mt-2">JPG, PNG, WebP o MP4. Máximo 5 MB.</p>
                @error('archivo') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-line">
                <a href="{{ route('admin.contenido.index', ['seccion' => $seccion]) }}"
                   class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</a>
                <button type="submit"
                        class="ig-btn ig-btn-primary !py-2.5 !px-5">Guardar</button>
            </div>
        </form>
    </div>
</x-admin-layout>