<x-app-layout>
    <div class="min-h-screen flex items-center justify-center bg-[#f5f3ec] py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-md border border-[#e8e6df]">
            <div class="text-center mb-8">
                <i data-lucide="key-round" class="mx-auto h-12 w-12 text-[#28533c]"></i>
                <h2 class="mt-4 text-2xl font-bold text-[#1a1a1a]">Restablecer contraseña</h2>
                <p class="mt-2 text-sm text-gray-600">Ingrese su nueva contraseña de acceso</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 text-red-600 p-4 rounded-md mb-6 text-sm font-medium border border-red-100">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="space-y-5" action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label class="block text-sm font-bold text-[#1a1a1a] mb-2">Nueva contraseña</label>
                    <input type="password" name="password" required class="w-full border border-gray-300 rounded px-4 py-3 focus:ring-2 focus:ring-[#28533c] outline-none">
                    <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, con mayúscula, minúscula, número y carácter especial.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-[#1a1a1a] mb-2">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded px-4 py-3 focus:ring-2 focus:ring-[#28533c] outline-none">
                </div>
                <button type="submit" class="w-full bg-[#28533c] text-white px-8 py-3.5 rounded text-sm font-semibold hover:bg-[#1e402e] transition-colors">
                    Restablecer contraseña
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
