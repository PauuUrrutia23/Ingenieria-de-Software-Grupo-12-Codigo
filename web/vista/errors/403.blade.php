<x-app-layout>
    {{-- CU 59.2, Excepciones 2 y 3: la acción se rechaza en el servidor y se informa el motivo. --}}
    <div class="min-h-[75vh] bg-paper flex items-center justify-center pt-24 pb-16 px-4">
        <div class="text-center max-w-xl">
            <p class="font-mono text-[6rem] leading-none font-normal tracking-tight text-carbon md:text-[8rem]">403</p>
            <span class="block w-16 h-px bg-wood mx-auto my-10" aria-hidden="true"></span>
            <h1 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-4">Acceso no permitido</h1>
            <p class="text-mute font-light mb-10">
                {{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Su cuenta no tiene permiso para realizar esta acción.' }}
            </p>
            <a href="/admin/dashboard" class="ig-btn ig-btn-primary">Volver al panel</a>
        </div>
    </div>
</x-app-layout>
