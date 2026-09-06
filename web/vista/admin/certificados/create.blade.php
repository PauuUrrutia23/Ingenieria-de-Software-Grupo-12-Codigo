<x-admin-layout>
    <x-slot name="header">Nuevo Certificado</x-slot>
    @if($errors->any()) <div class="bg-red-50 text-red-600 p-4 rounded mb-4"><ul>@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul></div> @endif
    <form action="{{ route('admin.certificados.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow max-w-4xl space-y-6">
        @csrf
        <div class="grid grid-cols-2 gap-6">
            <div><label class="block font-bold mb-2">Nombre</label><input type="text" name="nombre" class="w-full border p-2" required></div>
            <div><label class="block font-bold mb-2">Organismo</label><input type="text" name="organismo" class="w-full border p-2" required></div>
            <div><label class="block font-bold mb-2">URL Organismo</label><input type="url" name="url_organismo" class="w-full border p-2"></div>
        </div>
        <div><label class="block font-bold mb-2">Descripción</label><textarea name="descripcion" rows="3" class="w-full border p-2"></textarea></div>
        <div class="grid grid-cols-2 gap-6">
            <div><label class="block font-bold mb-2">Imagen Representativa</label><input type="file" name="imagen" accept="image/*" class="w-full border p-2"></div>
            <div><label class="block font-bold mb-2">Archivo PDF adjunto</label><input type="file" name="archivo_pdf" accept="application/pdf" class="w-full border p-2"></div>
        </div>
        <button type="submit" class="bg-[#28533c] text-white px-6 py-2 rounded font-bold">Guardar</button>
    </form>
</x-admin-layout>