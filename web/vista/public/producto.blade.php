<x-app-layout titulo="Conectores metálicos — Ingecon">

    {{-- ====================== RUTA DE NAVEGACIÓN ====================== --}}
    <div class="bg-surface border-b border-line pt-28 pb-5 lg:pt-32">
        <div class="ig-container">
            <nav class="ig-meta flex items-center gap-2.5" aria-label="Ruta de navegación">
                <a href="{{ route('public.producto') }}" class="text-mute hover:text-wood-deep transition-colors">
                    Productos
                </a>
                <span class="text-line-strong">/</span>
                <span class="!text-carbon">Conectores metálicos</span>
            </nav>
        </div>
    </div>

    <div class="ig-container">
        @if(session('doc_no_disponible'))
            <div class="ig-reveal flex items-start gap-4 border border-line border-l-2 !border-l-wood bg-paper px-5 py-4 mt-10">
                <i data-lucide="info" class="h-5 w-5 shrink-0 mt-0.5 text-wood-deep"></i>
                <p class="text-mute-deep font-light text-sm leading-relaxed">{{ session('doc_no_disponible') }}</p>
            </div>
        @endif
    </div>

    {{-- ====================== FICHA TÉCNICA DEL PRODUCTO ====================== --}}
    <section class="ig-section bg-surface">
        <div class="ig-container">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 lg:gap-20 items-start">

                {{-- Zona visual: lámina principal + miniaturas --}}
                <div class="ig-reveal">
                    <div class="ig-shot aspect-[4/3] border border-line mb-4">
                        <img src="{{ asset($ficha['imagen_principal']['src']) }}"
                             alt="{{ $ficha['imagen_principal']['alt'] }}"
                             loading="lazy" width="1448" height="1086">
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        @foreach($ficha['miniaturas'] as $miniatura)
                            <div class="ig-shot aspect-square border border-line">
                                <img src="{{ asset($miniatura['src']) }}" alt="{{ $miniatura['alt'] }}"
                                     loading="lazy" width="1448" height="1086">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Datos del producto --}}
                <div class="ig-reveal ig-d1">
                    <p class="ig-eyebrow mb-6">Producto</p>
                    <h1 class="ig-h-display mb-8 !text-[clamp(2rem,4.5vw,3.25rem)]">Conectores metálicos</h1>

                    <p class="ig-lede mb-12">
                        {{ $ficha['descripcion'] }}
                    </p>

                    <h2 class="ig-meta !text-wood-deep mb-5">Especificaciones técnicas</h2>
                    <dl class="border-t border-line mb-12">
                        @foreach($ficha['especificaciones'] as $spec)
                            <div class="grid grid-cols-2 gap-6 border-b border-line py-4">
                                <dt class="ig-meta">{{ $spec['label'] }}</dt>
                                <dd class="text-carbon font-light text-sm">{{ $spec['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/#contacto" class="ig-btn ig-btn-primary flex-1">
                            Consultar por este producto
                        </a>
                        <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="ig-btn ig-btn-secondary flex-1">
                            <i data-lucide="download" class="h-4 w-4"></i> Ficha técnica
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ DÓNDE SE USAN ============================ --}}
    <section class="ig-section bg-paper-deep border-y border-line">
        <div class="ig-container">
            <div class="max-w-2xl mb-14 lg:mb-16 ig-reveal">
                <p class="ig-eyebrow mb-5">Aplicaciones</p>
                <h2 class="ig-h-section">Dónde se usan</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-line border border-line">
                @foreach($ficha['aplicaciones'] as $uso)
                    <div class="bg-paper-deep p-8 lg:p-10 ig-reveal {{ $loop->index ? 'ig-d' . $loop->index : '' }}">
                        <i data-lucide="{{ $uso['icono'] }}" class="h-7 w-7 text-wood-deep mb-8"></i>
                        <h3 class="font-display text-xl font-medium mb-3">{{ $uso['titulo'] }}</h3>
                        <p class="text-mute font-light leading-relaxed">{{ $uso['texto'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ====================== DOCUMENTACIÓN TÉCNICA (banda oscura) ====================== --}}
    <section class="ig-section bg-carbon text-white ig-blueprint">
        <div class="ig-container">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-center">
                <div class="lg:col-span-8 ig-reveal">
                    <p class="ig-eyebrow ig-eyebrow-on-dark mb-5">Especificadores y calculistas</p>
                    <h2 class="ig-h-section mb-6 !text-white">Documentación técnica</h2>
                    <p class="ig-lede !text-white/60">
                        Planillas de cálculo, tablas de carga y detalles de montaje para
                        especificadores y calculistas.
                    </p>
                </div>
                <div class="lg:col-span-4 lg:justify-self-end ig-reveal ig-d1">
                    <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="ig-btn ig-btn-hero">
                        <span class="ig-btn-label">
                            Ver documentación
                            <i data-lucide="arrow-right" class="ig-btn-arrow h-4 w-4"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
