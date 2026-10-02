<x-admin-layout>
    <x-slot name="header">Editar Certificado</x-slot>

    @if($errors->any())
        <div class="mb-6 border border-[#b4403f] bg-surface px-5 py-3 text-sm text-[#8c2f2f]">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.certificados.update', $certificado) }}" method="POST" enctype="multipart/form-data"
          class="ig-card max-w-3xl">
        @csrf @method('PUT')
        <div class="border-b border-line px-6 py-4 flex items-center justify-between gap-3">
            <p class="ig-eyebrow">Ficha de certificación</p>
            <span class="ig-badge
                {{ match($certificado->estado) {
                    'vigente' => 'ig-badge-ok',
                    'vencido' => 'ig-badge-warn',
                    default => 'ig-badge-mute',
                } }}">{{ ucfirst($certificado->estado) }}</span>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="ec-nombre" class="ig-label">Nombre de la Normativa</label>
                    <input id="ec-nombre" type="text" name="nombre" value="{{ old('nombre', $certificado->nombre) }}"
                           class="ig-field-box" required>
                </div>
                <div>
                    <label for="ec-estado" class="ig-label">Estado</label>
                    <select id="ec-estado" name="estado" class="ig-field-box cursor-pointer">
                        <option value="vigente" {{ $certificado->estado == 'vigente' ? 'selected' : '' }}>Vigente</option>
                        <option value="vencido" {{ $certificado->estado == 'vencido' ? 'selected' : '' }}>Vencido</option>
                        <option value="revocado" {{ $certificado->estado == 'revocado' ? 'selected' : '' }}>Revocado</option>
                    </select>
                </div>
                <div>
                    <label for="ec-organismo" class="ig-label">Organismo Certificador</label>
                    <input id="ec-organismo" type="text" name="organismo" value="{{ old('organismo', $certificado->organismo) }}"
                           class="ig-field-box" required>
                </div>
                <div>
                    <label for="ec-url" class="ig-label">Enlace del Organismo</label>
                    <input id="ec-url" type="url" name="url_organismo" value="{{ old('url_organismo', $certificado->url_organismo) }}"
                           class="ig-field-box">
                </div>
            </div>

            <div>
                <label for="ec-desc" class="ig-label">Descripción</label>
                <textarea id="ec-desc" name="descripcion" rows="3"
                          class="ig-field-box resize-y">{{ old('descripcion', $certificado->descripcion) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="ec-imagen" class="ig-label">Imagen Representativa <span class="normal-case tracking-normal text-mute">(dejar vacío para mantener)</span></label>
                    <input id="ec-imagen" type="file" name="imagen" accept="image/*"
                           class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong hover:file:bg-wood hover:file:text-carbon">
                    @if($certificado->imagen)
                        <img src="{{ Storage::url($certificado->imagen) }}" alt="" class="mt-2 h-16 border border-line bg-paper p-1">
                    @endif
                </div>
                <div>
                    <label for="ec-pdf" class="ig-label">Archivo PDF <span class="normal-case tracking-normal text-mute">(dejar vacío para mantener)</span></label>
                    <input id="ec-pdf" type="file" name="archivo_pdf" accept="application/pdf"
                           class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong hover:file:bg-wood hover:file:text-carbon">
                    @if($certificado->archivo_pdf)
                        <a href="{{ Storage::url($certificado->archivo_pdf) }}" target="_blank" rel="noopener" class="ig-link-underline mt-2 inline-block">Ver PDF actual</a>
                    @endif
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.certificados.index') }}"
                   class="ig-btn ig-btn-secondary">Cancelar</a>
                <button type="submit"
                        class="ig-btn ig-btn-primary">Actualizar</button>
            </div>
        </div>
    </form>
</x-admin-layout>
