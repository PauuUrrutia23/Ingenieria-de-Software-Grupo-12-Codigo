<x-admin-layout>
    <x-slot name="header">Detalle de Consulta #{{ $consulta->id_consulta }}</x-slot>

    @if(session('success')) <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div> @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Mensaje del Visitante</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $consulta->mensaje }}</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Datos de Contacto</h3>
                <p><strong>Nombre:</strong> {{ $consulta->visitante->nombre }} {{ $consulta->visitante->apellido }}</p>
                <p><strong>Email:</strong> <a href="mailto:{{ $consulta->visitante->email }}" class="text-blue-600 underline">{{ $consulta->visitante->email }}</a></p>
                <p><strong>Fecha:</strong> {{ $consulta->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-[#28533c]">
                <h3 class="font-bold text-lg mb-4 border-b pb-2">Gestión Comercial</h3>
                <form action="{{ route('admin.consultas.update', $consulta) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    
                    <div>
                        <label class="block font-bold mb-1">Estado actual</label>
                        <select name="estado" class="w-full border p-2 rounded">
                            <option value="pendiente" {{ $consulta->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="en_proceso" {{ $consulta->estado == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                            <option value="finalizada" {{ $consulta->estado == 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold mb-1">Prioridad (Interna)</label>
                        <select name="prioridad" class="w-full border p-2 rounded">
                            <option value="">Seleccione...</option>
                            <option value="baja" {{ $consulta->prioridad == 'baja' ? 'selected' : '' }}>Baja</option>
                            <option value="media" {{ $consulta->prioridad == 'media' ? 'selected' : '' }}>Media</option>
                            <option value="alta" {{ $consulta->prioridad == 'alta' ? 'selected' : '' }}>Alta</option>
                        </select>
                    </div>

                    <p class="text-sm text-gray-500">Responsable asignado: <strong>{{ $consulta->adminResponsable->correo ?? 'Usted será asignado al guardar' }}</strong></p>

                    <button type="submit" class="w-full bg-[#28533c] text-white px-4 py-2 rounded font-bold hover:bg-[#1e402e]">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>