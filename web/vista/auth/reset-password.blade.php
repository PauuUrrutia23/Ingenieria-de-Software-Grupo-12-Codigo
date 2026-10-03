<x-app-layout>
    <div class="min-h-screen bg-paper pt-32 pb-24">
        <div class="ig-container">
            <div class="max-w-[480px] mx-auto">

                <div class="flex items-center justify-between mb-8">
                    <p class="ig-eyebrow">Recuperación de acceso</p>
                    <span class="ig-meta">Ref. contraseña</span>
                </div>

                <div class="bg-surface border border-line">
                    <div class="h-1 bg-carbon">
                        <div class="h-1 w-1/4 bg-wood"></div>
                    </div>

                    <div class="px-6 sm:px-10 py-10">
                        <h1 class="ig-h-section mb-2">Restablecer contraseña</h1>
                        <p class="text-sm text-mute mb-10">Ingrese su nueva contraseña de acceso.</p>

                        @if ($errors->any())
                            <div class="mb-8 border border-[#b4403f]/40 bg-[#f8f1f0] px-4 py-3 text-sm font-medium text-[#8c2f2f]" role="alert">
                                <span class="flex items-start gap-2">
                                    <i data-lucide="alert-triangle" class="h-4 w-4 mt-0.5 shrink-0"></i>
                                    {{ $errors->first() }}
                                </span>
                            </div>
                        @endif

                        <form class="space-y-7" action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div>
                                <label for="password" class="ig-label">Nueva contraseña</label>
                                <input id="password" type="password" name="password" required
                                       class="ig-input" autocomplete="new-password">
                                <p class="mt-2 text-xs text-mute font-light">
                                    Mínimo 8 caracteres, con mayúscula, minúscula, número y carácter especial.
                                </p>
                            </div>

                            <div>
                                <label for="password-confirmation" class="ig-label">Confirmar nueva contraseña</label>
                                <input id="password-confirmation" type="password" name="password_confirmation" required
                                       class="ig-input" autocomplete="new-password">
                            </div>

                            <button type="submit" class="w-full ig-btn ig-btn-primary">
                                Restablecer contraseña
                            </button>
                        </form>
                    </div>

                    <div class="px-6 sm:px-10 py-5 border-t border-line bg-paper-deep">
                        <p class="ig-meta">Panel de Gestión · Ingecon</p>
                    </div>
                </div>

                <p class="mt-8 text-center text-xs text-mute font-light">
                    <a href="{{ route('login') }}" class="text-wood-deep hover:text-carbon transition-colors">Volver al acceso</a>
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
