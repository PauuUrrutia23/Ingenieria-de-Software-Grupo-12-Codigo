<x-app-layout titulo="Colaboradores — Ingecon">

    <section class="bg-surface border-b border-line pt-32 pb-16 lg:pt-40 lg:pb-24">
        <div class="ig-container">
            <div class="max-w-3xl ig-reveal">
                <p class="ig-eyebrow mb-6">Alianzas</p>
                <h1 class="ig-h-display mb-8">Colaboradores</h1>
                <p class="ig-lede">
                    Colaboradores y socios estratégicos que respaldan nuestros proyectos.
                </p>
            </div>
        </div>
    </section>

    <section class="ig-section bg-paper">
        <div class="ig-container">

            @if($colaboradores->isEmpty())
                <div class="ig-reveal ig-card px-8 py-20 text-center">
                    <div class="mx-auto mb-6 h-14 w-14 border border-line-strong flex items-center justify-center bg-paper-deep">
                        <i data-lucide="users" class="h-6 w-6 text-mute"></i>
                    </div>
                    <p class="ig-lede">Aún no hay colaboradores.</p>
                </div>
            @else
                <p class="ig-meta ig-reveal mb-10">Aliados registrados · {{ count($colaboradores) }}</p>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-px bg-line border border-line ig-reveal">
                    @foreach($colaboradores as $prov)
                        <div class="flex flex-col items-center justify-center bg-surface px-6 py-12 group">
                            <div class="flex items-center justify-center h-24 w-full mb-6">
                                <img src="{{ $prov->logo_url }}"
                                     alt="{{ $prov->nombre_comercial }}" loading="lazy"
                                     class="max-h-full max-w-full object-contain grayscale opacity-60 transition-all duration-500 group-hover:grayscale-0 group-hover:opacity-100">
                            </div>
                            <span class="ig-meta !text-mute-deep text-center leading-relaxed">
                                {{ $prov->nombre_comercial }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
