<x-admin-layout>
    <x-slot name="header">Nuevo Certificado</x-slot>

    @if($errors->any())
        <div class="mb-6 border border-[#b4403f] bg-surface px-5 py-3 text-sm text-[#8c2f2f]">
            <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.certificados.store') }}" method="POST" enctype="multipart/form-data" class="ig-card max-w-3xl">
        @csrf
        <div class="border-b border-line px-6 py-4">
            <p class="ig-eyebrow">Registro de certificación</p>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="n-cert-nombre" class="ig-label">Nombre</label>
                    <input id="n-cert-nombre" type="text" name="nombre" value="{{ old('nombre') }}" class="ig-field-box" required>
                </div>
                <div>
                    <label for="n-cert-organismo" class="ig-label">Organismo</label>
                    <input id="n-cert-organismo" type="text" name="organismo" value="{{ old('organismo') }}" class="ig-field-box" required>
                </div>
                <div class="sm:col-span-2">
                    <label for="n-cert-url" class="ig-label">URL Organismo</label>
                    <input id="n-cert-url" type="url" name="url_organismo" value="{{ old('url_organismo') }}" class="ig-field-box">
                </div>
            </div>

            <div>
                <label for="n-cert-desc" class="ig-label">Descripción</label>
                <textarea id="n-cert-desc" name="descripcion" rows="3" class="ig-field-box resize-y">{{ old('descripcion') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="n-cert-imagen" class="ig-label">Imagen Representativa</label>
                    <input id="n-cert-imagen" type="file" name="imagen" accept="image/*"
                           class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong">
                </div>
                <div>
                    <label for="n-cert-pdf" class="ig-label">Archivo PDF adjunto</label>
                    <input id="n-cert-pdf" type="file" name="archivo_pdf" accept="application/pdf"
                           class="block w-full cursor-pointer border border-line bg-surface p-2 text-sm text-mute-deep file:mr-3 file:border-0 file:bg-carbon file:px-3 file:py-1.5 file:font-mono file:text-[0.625rem] file:uppercase file:tracking-[0.14em] file:text-white hover:border-line-strong">
                </div>
            </div>
        </div>

        <div class="border-t border-line px-6 py-4 flex items-center justify-end gap-3">
            <a href="{{ route('admin.certificados.index') }}" class="ig-btn ig-btn-secondary">Cancelar</a>
            <button type="submit" class="ig-btn ig-btn-primary">Guardar</button>
        </div>
    </form>
</x-admin-layout>
