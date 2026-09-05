<x-admin-layout>
    <x-slot name="header">Nuevo Proveedor</x-slot>
    @if($errors->any()) <div class="bg-red-50 text-red-600 p-4 rounded mb-4"><ul>@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul></div> @endif
    <form action="{{ route('admin.colaboradores.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow max-w-2xl space-y-6">
        @csrf
        <div><label class="block font-bold mb-2">Nombre Comercial</label><input type="text" name="nombre_comercial" class="w-full border p-2" required></div>
        <div><label class="block font-bold mb-2">Logotipo</label><input type="file" name="logotipo" accept="image/*" class="w-full border p-2" required></div>
        <button type="submit" class="bg-[#28533c] text-white px-6 py-2 rounded font-bold">Guardar</button>
    </form>
</x-admin-layout>