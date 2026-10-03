@props(['show', 'titulo' => '', 'ancho' => 'max-w-2xl', 'errores' => false])

<div x-show="{{ $show }}" style="display: none;"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-6"
     @keydown.escape.window="{{ $show }} = false"
     x-trap.noscroll="{{ $show }}"
     role="dialog" aria-modal="true">

    <div class="absolute inset-0 bg-carbon/65 backdrop-blur-[3px]" @click="{{ $show }} = false" aria-hidden="true"></div>

    <div @click.stop
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="relative z-10 bg-surface border border-line-strong w-full {{ $ancho }} max-h-[90vh] overflow-y-auto flex flex-col shadow-[0_18px_40px_-24px_rgba(17,19,21,0.45)]">

        <div class="flex items-start justify-between gap-6 px-5 sm:px-7 py-4 sm:py-5 border-b border-line bg-surface sticky top-0 z-10">
            <div class="min-w-0">
                @if($titulo !== '')
                    <h3 class="font-display text-[1.0625rem] sm:text-xl font-light tracking-[-0.015em] leading-snug text-carbon">{{ $titulo }}</h3>
                @endif
            </div>
            <button type="button" @click="{{ $show }} = false" aria-label="Cerrar ventana"
                    class="shrink-0 -mr-1 p-1.5 text-mute hover:text-carbon transition-colors">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="px-5 sm:px-7 py-5 sm:py-6">
            @if($errores && $errors->any())
                <div role="alert" style="border: 1px solid #b4403f; background: #fbeeee; color: #8a2b2b; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; line-height: 1.5;">
                    <p style="font-weight: 600; margin-bottom: 0.25rem;">No se pudo guardar:</p>
                    <ul style="list-style: disc; padding-left: 1.25rem; margin: 0;">
                        @foreach($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
