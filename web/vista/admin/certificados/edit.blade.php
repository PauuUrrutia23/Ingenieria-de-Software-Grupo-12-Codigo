<x-admin-layout>
    <x-slot name="header">Editar Certificado</x-slot>

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm px-5 py-3 rounded-xl">
            <ul class="list-disc list-inside">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.certificados.update', $certificado) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-3xl space-y-5">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="ec-codigo" class="block text-sm font-semibold text-slate-700 mb-1.5">Código</label>
                <input id="ec-codigo" type="text" name="codigo" value="{{ $certificado->codigo }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ec-nombre" class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de la Normativa</label>
                <input id="ec-nombre" type="text" name="nombre" value="{{ $certificado->nombre }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ec-fecha" class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha Emisión</label>
                <input id="ec-fecha" type="date" name="fecha_emision" value="{{ $certificado->fecha_emision->format('Y-m-d') }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ec-estado" class="block text-sm font-semibold text-slate-700 mb-1.5">Estado</label>
                <select id="ec-estado" name="estado" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 cursor-pointer">
                    <option value="vigente" {{ $certificado->estado == 'vigente' ? 'selected' : '' }}>Vigente</option>
                    <option value="vencido" {{ $certificado->estado == 'vencido' ? 'selected' : '' }}>Vencido</option>
                    <option value="revocado" {{ $certificado->estado == 'revocado' ? 'selected' : '' }}>Revocado</option>
                </select>
            </div>
            <div>
                <label for="ec-organismo" class="block text-sm font-semibold text-slate-700 mb-1.5">Organismo Certificador</label>
                <input id="ec-organismo" type="text" name="organismo" value="{{ $certificado->organismo }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" required>
            </div>
            <div>
                <label for="ec-url" class="block text-sm font-semibold text-slate-700 mb-1.5">Enlace del Organismo</label>
                <input id="ec-url" type="url" name="url_organismo" value="{{ $certificado->url_organismo }}"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
            </div>
        </div>

        <div>
            <label for="ec-desc" class="block text-sm font-semibold text-slate-700 mb-1.5">Descripción</label>
            <textarea id="ec-desc" name="descripcion" rows="3"
                      class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 resize-none">{{ $certificado->descripcion }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="ec-imagen" class="block text-sm font-semibold text-slate-700 mb-1.5">Imagen Representativa <span class="font-normal text-slate-400">(dejar vacío para mantener)</span></label>
                <input id="ec-imagen" type="file" name="imagen" accept="image/*"
                       class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                @if($certificado->imagen)
                    <img src="{{ Storage::url($certificado->imagen) }}" alt="" class="mt-2 h-16 object-contain rounded border border-slate-100 p-1 bg-slate-50">
                @endif
            </div>
            <div>
                <label for="ec-pdf" class="block text-sm font-semibold text-slate-700 mb-1.5">Archivo PDF <span class="font-normal text-slate-400">(dejar vacío para mantener)</span></label>
                <input id="ec-pdf" type="file" name="archivo_pdf" accept="application/pdf"
                       class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                @if($certificado->archivo_pdf)
                    <a href="{{ Storage::url($certificado->archivo_pdf) }}" target="_blank" class="mt-2 text-sm text-slate-600 hover:text-slate-900 hover:underline block">Ver PDF actual</a>
                @endif
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.certificados.index') }}"
               class="px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancelar</a>
            <button type="submit"
                    class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">Actualizar</button>
        </div>
    </form>
</x-admin-layout>
