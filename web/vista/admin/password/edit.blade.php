<x-admin-layout>
    <x-slot name="header">Cambiar Contraseña</x-slot>

    <div class="mb-8 -mt-2">
        <p class="ig-eyebrow mb-2">Cuenta de administrador</p>
        <p class="text-mute text-sm">La nueva contraseña aplica a partir del próximo inicio de sesión.</p>
    </div>

    @if(session('success'))
        <div class="ig-badge-ok flex items-center gap-3 border px-5 py-3 mb-6 text-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-paper-deep border border-line-strong text-wood-deep text-sm px-5 py-3 mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="ig-card max-w-md">
        <div class="bg-paper-deep border-b border-line px-5 py-3">
            <h2 class="ig-eyebrow">Actualizar credencial</h2>
        </div>
        <form action="{{ route('admin.password.update') }}" method="POST" class="p-5 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="pw-actual" class="ig-label">Contraseña actual</label>
                <input id="pw-actual" type="password" name="password_actual" autocomplete="current-password" required
                       class="ig-field-box @error('password_actual') ig-field-box-invalid @enderror">
                @error('password_actual') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-line pt-5">
                <label for="pw-nueva" class="ig-label">Nueva contraseña</label>
                <input id="pw-nueva" type="password" name="password" autocomplete="new-password" required
                       class="ig-field-box @error('password') ig-field-box-invalid @enderror">
                <p class="text-xs text-mute mt-2">Mínimo 8 caracteres, con mayúscula, minúscula, número y carácter especial.</p>
                @error('password') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="pw-confirmar" class="ig-label">Confirmar nueva contraseña</label>
                <input id="pw-confirmar" type="password" name="password_confirmation" autocomplete="new-password" required
                       class="ig-field-box @error('password_confirmation') ig-field-box-invalid @enderror">
                @error('password_confirmation') <p class="ig-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="ig-btn ig-btn-primary w-full">
                Guardar cambios
            </button>
        </form>
    </div>
</x-admin-layout>