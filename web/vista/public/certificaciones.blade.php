<x-app-layout titulo="Certificaciones — Ingecon">

    <section class="bg-carbon text-white ig-blueprint pt-32 pb-16 lg:pt-40 lg:pb-24 border-b border-carbon">
        <div class="ig-container">
            <div class="max-w-3xl ig-reveal">
                <p class="ig-eyebrow ig-eyebrow-on-dark mb-6">Marco técnico</p>
                <h1 class="ig-h-display mb-8 !text-white">
                    Certificaciones <span class="font-normal text-wood-light">vigentes</span>
                </h1>
                <p class="ig-lede !text-white/60">
                    Normativa chilena y respaldo de organismos certificadores para cada
                    producto y proceso.
                </p>
            </div>
        </div>
    </section>

    <section class="ig-section bg-surface">
        <div class="ig-container">

            @if(session('doc_no_disponible'))
                <div class="ig-reveal flex items-start gap-4 border border-line border-l-2 !border-l-wood bg-paper px-5 py-4 mb-14">
                    <i data-lucide="info" class="h-5 w-5 shrink-0 mt-0.5 text-wood-deep"></i>
                    <p class="text-mute-deep font-light text-sm leading-relaxed">{{ session('doc_no_disponible') }}</p>
                </div>
            @endif

            @if($certificados->isEmpty())
                <div class="ig-reveal ig-card px-8 py-20 text-center">
                    <div class="mx-auto mb-6 h-14 w-14 border border-line-strong flex items-center justify-center bg-paper-deep">
                        <i data-lucide="shield-check" class="h-6 w-6 text-mute"></i>
                    </div>
                    <p class="ig-lede">Aún no hay certificaciones publicadas.</p>
                </div>
            @else
                <div class="border-t border-line">
                    @foreach($certificados as $cert)
                        @php
                            $estado = $cert->estado ?? 'vigente';
                            $claseEstado = match ($estado) {
                                'vigente' => 'ig-badge-ok',
                                'vencido' => 'ig-badge-warn',
                                default    => 'ig-badge-mute',
                            };
                        @endphp
                        <article class="grid grid-cols-1 md:grid-cols-12 gap-6 md:gap-10 items-start border-b border-line py-10 lg:py-12 ig-reveal ig-d{{ min($loop->index + 1, 4) }}">

                            <div class="md:col-span-3 flex md:block items-center gap-5">
                                <div class="relative shrink-0 aspect-square w-20 md:w-full bg-paper-deep border border-line overflow-hidden">
                                    @if($cert->imagen)
                                        <img src="{{ Storage::url($cert->imagen) }}"
                                             alt="{{ $cert->nombre }}" loading="lazy"
                                             class="absolute inset-0 w-full h-full object-contain p-2.5">
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <i data-lucide="shield-check" class="h-8 w-8 text-wood-deep"></i>
                                        </div>
                                    @endif
                                </div>
                                <span class="md:hidden ig-meta !text-wood-deep">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>

                            <div class="md:col-span-9">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-3 mb-3">
                                    <div class="flex items-baseline gap-4">
                                        <span class="hidden md:block ig-meta !text-wood-deep">
                                            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <h3 class="font-display text-2xl md:text-[1.75rem] font-light tracking-tight text-carbon" style="overflow-wrap: anywhere">
                                            {{ $cert->nombre }}
                                        </h3>
                                    </div>
                                    <span class="ig-badge {{ $claseEstado }}">{{ $estado }}</span>
                                </div>

                                @if($cert->organismo && $cert->url_organismo)
                                    <a href="{{ $cert->url_organismo }}" target="_blank" rel="noopener"
                                       class="ig-link-ghost !normal-case !tracking-normal !text-sm mb-5 text-mute-deep">
                                        <i data-lucide="badge-check" class="h-4 w-4 text-wood-deep"></i>
                                        {{ $cert->organismo }}
                                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                    </a>
                                @elseif($cert->organismo)
                                    <span class="ig-link-ghost !normal-case !tracking-normal !text-sm mb-5 text-mute-deep">
                                        <i data-lucide="badge-check" class="h-4 w-4 text-wood-deep"></i>
                                        {{ $cert->organismo }}
                                    </span>
                                @endif

                                <p class="text-mute font-light leading-relaxed max-w-3xl mb-7" style="overflow-wrap: anywhere">{{ $cert->descripcion }}</p>

                                <div class="flex flex-wrap items-center gap-x-8 gap-y-3 border-t border-line pt-5">
                                    @if($cert->created_at)
                                        <span class="ig-meta">Emitido {{ $cert->created_at->format('m/Y') }}</span>
                                    @endif

                                    @if($cert->archivo_pdf)
                                        <a href="{{ route('public.certificaciones.preview', $cert) }}" target="_blank" rel="noopener"
                                           class="ig-link-ghost">
                                            <i data-lucide="file-text" class="h-4 w-4"></i> Ver certificado (PDF)
                                        </a>
                                        <a href="{{ route('public.certificaciones.descargar', $cert) }}"
                                           class="ig-link-ghost">
                                            <i data-lucide="download" class="h-4 w-4"></i> Descargar certificado
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
