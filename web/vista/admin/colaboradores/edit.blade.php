<x-admin-layout>
    <x-slot name="header">Editar Proveedor</x-slot>

    <div class="mb-8 -mt-2">
        <p class="ig-eyebrow mb-2">Alianzas</p>
        <p class="text-mute text-sm">Actualizar los datos de <span class="text-carbon">{{ $colaborador->nombre_comercial }}</span>.</p>
    </div>

    @if($errors->any())
        <div class="bg-paper-deep border border-line-strong text-wood-deep text-sm px-5 py-3 mb-6">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.colaboradores.update', $colaborador) }}" method="POST" enctype="multipart/form-data"
          class="ig-card max-w-2xl p-6 space-y-6">
        @csrf @method('PUT')

        <div>
            <label for="nombre-comercial" class="ig-label">Nombre Comercial</label>
            <input id="nombre-comercial" type="text" name="nombre_comercial" value="{{ $colaborador->nombre_comercial }}"
                   class="ig-field-box" required>
        </div>

        <div>
            <span class="ig-label block">Logotipo actual</span>
            <div class="relative w-32 h-24 border border-line mb-4">
                <span class="ig-plate" aria-hidden="true"></span>
                <img src="{{ Storage::url($colaborador->logotipo) }}" alt="{{ $colaborador->nombre_comercial }}"
                     class="relative z-10 w-full h-full object-contain p-2">
            </div>
            <label for="logotipo" class="ig-label">Logotipo (dejar vacío para mantener el actual)</label>
            <input id="logotipo" type="file" name="logotipo" accept="image/jpeg,image/png" class="ig-field-box cursor-pointer">
            <p class="ig-meta mt-2">Máximo 500 KB (RNF17).</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="ig-btn ig-btn-primary !py-2.5 !px-5">Actualizar</button>
            <a href="{{ route('admin.colaboradores.index') }}" class="ig-btn ig-btn-secondary !py-2.5 !px-5">Cancelar</a>
        </div>
    </form>
</x-admin-layout>