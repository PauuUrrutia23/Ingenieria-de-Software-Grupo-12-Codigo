<x-admin-layout>
    <x-slot name="header">Cambiar Contraseña</x-slot>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium px-5 py-3 rounded-xl">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm px-5 py-3 rounded-xl">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-md">
        <form action="{{ route('admin.password.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="pw-actual" class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña actual</label>
                <input id="pw-actual" type="password" name="password_actual" required
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
            </div>
            <div>
                <label for="pw-nueva" class="block text-sm font-semibold text-slate-700 mb-1.5">Nueva contraseña</label>
                <input id="pw-nueva" type="password" name="password" required
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                <p class="text-xs text-slate-400 mt-1.5">Mínimo 8 caracteres, con mayúscula, minúscula, número y carácter especial.</p>
            </div>
            <div>
                <label for="pw-confirmar" class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar nueva contraseña</label>
                <input id="pw-confirmar" type="password" name="password_confirmation" required
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
            </div>
            <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                Guardar cambios
            </button>
        </form>
    </div>
</x-admin-layout>
