<x-app-layout titulo="Ingecon — Industrialización de la madera y construcción prefabricada">

    @if($banner)
        <section data-ig-hero-overlay
                 class="relative min-h-[100svh] flex items-end bg-carbon text-white overflow-hidden">
            @if($banner->imagen_url)
                <img src="{{ $banner->imagen_url }}" alt="{{ $banner->cuerpo }}"
                     fetchpriority="high" class="ig-hero-shot absolute inset-0 w-full h-full object-cover opacity-55">
            @elseif($banner->video_url)
                <video class="ig-hero-shot absolute inset-0 w-full h-full object-cover opacity-55"
                       autoplay muted loop playsinline aria-hidden="true">
                    <source src="{{ $banner->video_url }}" type="video/mp4">
                </video>
            @else
                <div class="absolute inset-0 ig-blueprint opacity-70"></div>
            @endif

            <div class="absolute inset-0"
                 style="background: linear-gradient(to bottom, rgba(17,19,21,0.86) 0%, rgba(17,19,21,0.55) 45%, rgba(17,19,21,0.94) 100%);"></div>

            <div class="relative z-10 ig-container w-full pt-40 pb-28">
                <div class="max-w-3xl">
                    <h1 class="ig-h-display ig-reveal mb-12 !text-white">{{ $banner->cuerpo }}</h1>
                    <div class="ig-reveal flex flex-col sm:flex-row gap-4">
                        <a href="/proyectos" class="ig-btn ig-btn-hero">
                            <span class="ig-btn-label">Ver proyectos ejecutados</span>
                        </a>
                        <a href="#productos" class="ig-btn ig-btn-hero !border-white/20 !text-white/70">
                            <span class="ig-btn-label">Ver productos</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section id="productos" class="ig-section bg-surface ig-anchor">
        <div class="ig-container">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16 lg:mb-20">
                <div class="ig-reveal max-w-2xl">
                    <p class="ig-eyebrow mb-4">Qué hacemos</p>
                    <h2 class="ig-h-section mb-5">Productos</h2>
                    <p class="ig-meta">{{ now()->year - 1994 }} años de experiencia — desde 1994</p>
                </div>
            </div>

            <div class="border-t border-line">
                @forelse($productos as $producto)
                    <article class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-8 items-start py-9 border-b border-line">
                        <span class="md:col-span-1 ig-meta !text-wood-deep">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="md:col-span-4">
                            <h3 class="font-display text-2xl font-light tracking-tight text-carbon">{{ $producto->nombre }}</h3>
                            <p class="text-mute font-light leading-relaxed mt-3">{{ $producto->descripcion }}</p>
                        </div>
                        <div class="md:col-span-4">
                            @if($producto->componentes->isNotEmpty())
                                <ul class="list-disc pl-5 text-mute-deep">
                                    @foreach($producto->componentes as $componente)
                                        <li>{{ $componente->nombre }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-mute">Sin componentes registrados.</p>
                            @endif
                        </div>
                        <div class="md:col-span-3 ig-shot h-36 w-full">
                            @if($producto->imagen_url)
                                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" loading="lazy" class="w-full h-full object-cover">
                            @else
                                <span class="flex items-center justify-center w-full h-full text-mute">Imagen no disponible.</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="ig-lede py-9">Aún no hay productos publicados.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="ig-section bg-paper-deep border-y border-line">
        <div class="ig-container">
            <div class="max-w-3xl mb-16 ig-reveal">
                <p class="ig-eyebrow mb-4">Nuestro proceso</p>
                <h2 class="ig-h-section mb-6">De la madera en bruto al elemento montado</h2>
                <p class="ig-lede">
                    Cada etapa es un proceso de trabajo con altos estándares que nos permite
                    asegurar la calidad mediante el control de un proceso de línea de gran volumen.
                </p>
            </div>

            @if($fasesIndustriales->isEmpty())
                <p class="ig-lede">Aún no hay etapas industriales publicadas.</p>
            @else
                <div x-data="fasesIndustriales()" class="border-y border-line">
                    <div role="tablist" aria-label="Etapas del proceso industrial"
                         class="grid grid-cols-1 md:grid-cols-3 gap-px bg-line">
                        @foreach($fasesIndustriales as $nombre => $registros)
                            <button type="button" role="tab"
                                    id="fase-tab-{{ $loop->index }}"
                                    aria-controls="fase-panel-{{ $loop->index }}"
                                    :aria-selected="activa === {{ $loop->index }} ? 'true' : 'false'"
                                    @click="activa = {{ $loop->index }}"
                                    :class="activa === {{ $loop->index }} ? 'border-wood text-carbon bg-surface' : 'border-transparent text-mute bg-paper-deep'"
                                    class="px-6 py-6 border-b-2 text-left font-display text-xl transition-colors">
                                <span class="ig-meta mr-3">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                {{ $nombre }}
                            </button>
                        @endforeach
                    </div>

                    @foreach($fasesIndustriales as $nombre => $registros)
                        <div role="tabpanel" id="fase-panel-{{ $loop->index }}"
                             aria-labelledby="fase-tab-{{ $loop->index }}"
                             x-show="activa === {{ $loop->index }}"
                             @if(!$loop->first) x-cloak @endif
                             class="bg-surface p-8 md:p-12">
                            <h3 class="font-display text-2xl mb-5">{{ $nombre }}</h3>
                            <p class="ig-lede">{{ $registros->first()->cuerpo }}</p>
                            @php $medios = $registros->filter(fn ($registro) => $registro->imagen_url || $registro->video_url); @endphp
                            @if($medios->isNotEmpty())
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-8">
                                    @foreach($medios as $imagen)
                                        <figure class="border border-line bg-paper-deep">
                                            @if($imagen->imagen_url)
                                                <button type="button" @click="abrirImagen($event.currentTarget)"
                                                    data-src="{{ $imagen->imagen_url }}"
                                                    data-descripcion="{{ $imagen->cuerpo }}"
                                                    data-alt="{{ $nombre }}: {{ $imagen->cuerpo }}"
                                                    aria-label="Ampliar imagen de {{ $nombre }}"
                                                    class="block w-full cursor-zoom-in">
                                                    <img src="{{ $imagen->imagen_url }}"
                                                     alt="{{ $nombre }}: {{ $imagen->cuerpo }}"
                                                     loading="lazy" class="w-full h-52 object-cover">
                                                </button>
                                            @else
                                                <video controls preload="metadata" playsinline class="w-full h-52 bg-carbon" aria-label="Video de {{ $nombre }}">
                                                    <source src="{{ $imagen->video_url }}" type="video/mp4">
                                                    Tu navegador no puede reproducir este video.
                                                </video>
                                            @endif
                                            <figcaption class="text-sm text-mute-deep p-4">{{ $imagen->cuerpo }}</figcaption>
                                        </figure>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div x-show="imagenAbierta !== null" x-cloak
                         @keydown.escape.window="cerrarImagen()"
                         class="fixed inset-0 z-[90] flex items-center justify-center p-4">
                        <div class="absolute inset-0 bg-carbon/85" @click="cerrarImagen()"></div>
                        <div role="dialog" aria-modal="true" aria-label="Imagen ampliada de la fase industrial"
                             class="relative bg-surface p-4 md:p-6 max-w-5xl w-full max-h-[90vh] overflow-auto">
                            <button type="button" x-ref="cerrarImagen" @click="cerrarImagen()"
                                    aria-label="Cerrar imagen ampliada"
                                    class="absolute right-4 top-4 z-10 bg-surface border border-line px-3 py-1 text-carbon">
                                Cerrar
                            </button>
                            <img :src="imagenAbierta?.src" :alt="imagenAbierta?.alt"
                                 class="w-full max-h-[72vh] object-contain bg-paper-deep">
                            <p x-text="imagenAbierta?.descripcion" class="text-mute-deep mt-4"></p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section id="proyectos" class="ig-section bg-surface ig-anchor">
        <div class="ig-container">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-14 lg:mb-20">
                <div class="ig-reveal">
                    <p class="ig-eyebrow mb-4">Obras en terreno</p>
                    <h2 class="ig-h-section">Proyectos recientes</h2>
                </div>
                <a href="/proyectos" class="ig-link-ghost ig-reveal ig-d1">
                    Ver galería completa <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>

            @if($proyectos_recientes->isEmpty())
                <p class="ig-lede ig-reveal">Pronto publicaremos nuestros proyectos recientes.</p>
            @else
                @php $destacado = $proyectos_recientes->first(); $secundarios = $proyectos_recientes->slice(1, 2); @endphp

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <a href="/proyectos" class="lg:col-span-7 ig-shot h-[340px] md:h-[480px] ig-reveal">
                        @if($destacado->imagenes->isNotEmpty())
                            <img src="{{ Storage::url($destacado->imagenes->first()->imagen) }}"
                                 alt="{{ $destacado->nombre_obra }}" loading="lazy">
                        @else
                            <span class="ig-plate"><span class="ig-plate-label">Sin fotografía</span></span>
                        @endif
                        <span class="ig-shot-veil"></span>
                        <span class="absolute bottom-0 left-0 w-full p-8 md:p-12 flex flex-col justify-end">
                            <span class="ig-meta !text-wood-light mb-3">{{ \App\Support\CategoriasProyecto::etiqueta($destacado->categoria) }}</span>
                            <span class="font-display text-2xl md:text-4xl font-light tracking-tight text-white">
                                {{ $destacado->nombre_obra }}
                            </span>
                            <span class="ig-meta !text-white/60 mt-4">
                                {{ $destacado->comuna }}, {{ $destacado->region }} · {{ $destacado->anio_ejecucion }}
                            </span>
                        </span>
                    </a>

                    <div class="lg:col-span-5 grid grid-cols-1 gap-4">
                        @forelse($secundarios as $i => $proyecto)
                        <a href="/proyectos" class="ig-shot h-[220px] md:h-[232px] ig-reveal ig-d{{ $i + 2 }}">
                            @if($proyecto->imagenes->isNotEmpty())
                                <img src="{{ Storage::url($proyecto->imagenes->first()->imagen) }}"
                                     alt="{{ $proyecto->nombre_obra }}" loading="lazy">
                            @else
                                <span class="ig-plate"><span class="ig-plate-label">Sin fotografía</span></span>
                            @endif
                            <span class="ig-shot-veil"></span>
                            <span class="absolute bottom-0 left-0 w-full p-7">
                                <span class="ig-meta !text-wood-light mb-2 block">{{ \App\Support\CategoriasProyecto::etiqueta($proyecto->categoria) }}</span>
                                <span class="font-display text-xl font-light tracking-tight text-white block">
                                    {{ $proyecto->nombre_obra }}
                                </span>
                                <span class="ig-meta !text-white/60 mt-3 block">
                                    {{ $proyecto->comuna }}, {{ $proyecto->region }} · {{ $proyecto->anio_ejecucion }}
                                </span>
                            </span>
                        </a>
                        @empty
                            <div class="ig-shot h-[220px] md:h-[468px] border border-line">
                                <span class="ig-plate"><span class="ig-plate-label">Obras por publicar</span></span>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if($proyectos_recientes->count() > 3)
                <div class="mt-14 border-t border-line">
                    @foreach($proyectos_recientes->slice(3) as $proyecto)
                    <a href="/proyectos"
                       class="group flex flex-wrap items-baseline gap-x-6 gap-y-2 py-5 border-b border-line ig-reveal">
                        <span class="font-display text-lg font-light text-carbon group-hover:text-wood-deep transition-colors">
                            {{ $proyecto->nombre_obra }}
                        </span>
                        <span class="ig-meta">{{ $proyecto->comuna }}, {{ $proyecto->region }}</span>
                        <span class="ig-meta ml-auto">{{ $proyecto->anio_ejecucion }}</span>
                    </a>
                    @endforeach
                </div>
                @endif
            @endif
        </div>
    </section>

    <section id="certificaciones" class="ig-section bg-carbon text-white ig-blueprint ig-anchor">
        <div class="ig-container">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-14 lg:gap-10">
                <div class="lg:col-span-5 ig-reveal">
                    <p class="ig-eyebrow ig-eyebrow-on-dark mb-5">Marco técnico</p>
                    <h2 class="ig-h-section mb-7 !text-white">Cumplimos la norma chilena</h2>
                    <p class="ig-lede mb-10 !text-white/60">
                        Nuestra madera y procesos están certificados para garantizar resistencia
                        y calidad. Cada obra cuenta con el respaldo que la ley exige.
                    </p>
                    <a href="/certificaciones" class="ig-btn ig-btn-hero">
                        <span class="ig-btn-label">
                            Ver certificados
                            <i data-lucide="arrow-right" class="ig-btn-arrow h-4 w-4"></i>
                        </span>
                    </a>
                </div>

                <div class="lg:col-span-7">
                    @if($certificados->isEmpty())
                        <p class="ig-lede !text-white/60">Certificaciones en proceso de actualización.</p>
                    @else
                        <div class="border-t border-white/12">
                            @foreach($certificados as $i => $cert)
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-6 items-baseline py-7 border-b border-white/12 ig-reveal ig-d{{ min($i + 1, 4) }} group">
                                <span class="sm:col-span-1 ig-meta !text-wood">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="sm:col-span-7">
                                    <h3 class="font-display text-lg font-light tracking-tight text-white mb-1.5">
                                        {{ $cert->nombre }}
                                    </h3>
                                </div>
                                <span class="sm:col-span-4 ig-meta !text-white/50 sm:text-right">{{ $cert->organismo }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="ig-section bg-surface">
        <div class="ig-container">
            <div class="text-center mb-14 ig-reveal">
                <p class="ig-eyebrow justify-center mb-4">Colaboradores</p>
                <h2 class="ig-h-section">Colaboradores estratégicos</h2>
            </div>

            @php $grupos = $proveedores->chunk(4); @endphp

            @if($grupos->isEmpty())
                <div class="text-center ig-lede ig-reveal">Pronto publicaremos nuestros colaboradores oficiales.</div>
            @else
                <div x-data="carruselColaboradores({{ $grupos->count() }})">
                    <div class="relative">
                        <div class="overflow-hidden">
                            <div class="flex transition-transform duration-700"
                                 style="transition-timing-function: cubic-bezier(0.16,1,0.3,1)"
                                 :style="`transform: translateX(-${actual * 100}%)`">
                                @foreach($grupos as $grupo)
                                    <div class="w-full shrink-0 grid grid-cols-2 md:grid-cols-4 gap-px bg-line">
                                        @foreach($grupo as $prov)
                                            <div class="flex flex-col items-center justify-center gap-3 bg-surface h-[150px] p-6 overflow-hidden">
                                                <img src="{{ $prov->logo_url }}"
                                                     alt="{{ $prov->nombre_comercial }}"
                                                     loading="lazy"
                                                     class="max-h-[100px] max-w-full object-contain grayscale opacity-65 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
                                                <span class="ig-meta truncate max-w-full">{{ $prov->nombre_comercial }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($grupos->count() > 1)
                            <button type="button" @click="anterior()" :disabled="actual === 0"
                                    aria-label="Ver colaboradores anteriores"
                                    class="absolute top-1/2 -translate-y-1/2 -left-3 md:-left-12 z-10 h-11 w-11 bg-surface border border-line flex items-center justify-center text-carbon transition-colors hover:border-wood hover:text-wood-deep disabled:opacity-30 disabled:cursor-not-allowed">
                                <i data-lucide="chevron-left" class="h-5 w-5"></i>
                            </button>
                            <button type="button" @click="siguiente()" :disabled="actual === total - 1"
                                    aria-label="Ver siguientes colaboradores"
                                    class="absolute top-1/2 -translate-y-1/2 -right-3 md:-right-12 z-10 h-11 w-11 bg-surface border border-line flex items-center justify-center text-carbon transition-colors hover:border-wood hover:text-wood-deep disabled:opacity-30 disabled:cursor-not-allowed">
                                <i data-lucide="chevron-right" class="h-5 w-5"></i>
                            </button>
                        @endif
                    </div>

                    @if($grupos->count() > 1)
                        <div class="mt-12 flex items-center justify-center gap-2">
                            @foreach($grupos as $i => $grupo)
                                <button type="button" @click="ir({{ $i }})"
                                        :aria-current="actual === {{ $i }} ? 'true' : 'false'"
                                        aria-label="Ver grupo {{ $i + 1 }} de {{ $grupos->count() }}"
                                        class="h-px transition-all duration-500"
                                        :class="actual === {{ $i }} ? 'w-12 bg-wood' : 'w-6 bg-line-strong hover:bg-mute'"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section id="opiniones" class="ig-section bg-surface border-t border-line ig-anchor">
        <div class="ig-container">
            <div class="text-center mb-14 ig-reveal">
                <p class="ig-eyebrow justify-center mb-4">Experiencias</p>
                <h2 class="ig-h-section">Opiniones de clientes</h2>
            </div>

            @if($opiniones->isEmpty())
                <p class="text-center ig-lede">Aún no hay opiniones publicadas.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-px bg-line border border-line">
                    @foreach($opiniones as $opinion)
                        <figure class="bg-surface p-8 md:p-10 flex flex-col justify-between gap-8">
                            @if($opinion->imagen_url)
                                <img src="{{ $opinion->imagen_url }}" alt="Imagen de {{ $opinion->titulo }}"
                                     loading="lazy" class="w-full h-44 object-cover">
                            @elseif($opinion->video_url)
                                <video controls preload="metadata" playsinline class="w-full h-44 bg-carbon" aria-label="Video de {{ $opinion->titulo }}">
                                    <source src="{{ $opinion->video_url }}" type="video/mp4">
                                </video>
                            @endif
                            <blockquote class="ig-lede">{{ $opinion->cuerpo }}</blockquote>
                            <figcaption class="font-display text-lg text-carbon">{{ $opinion->titulo }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="preguntas-frecuentes" class="ig-section bg-paper-deep border-t border-line ig-anchor">
        <div class="ig-container">
            <div class="max-w-3xl mx-auto">
                <div class="text-center mb-12 ig-reveal">
                    <p class="ig-eyebrow justify-center mb-4">Información útil</p>
                    <h2 class="ig-h-section">Preguntas frecuentes</h2>
                </div>

                @if($faqs->isEmpty())
                    <p class="text-center ig-lede">Aún no hay preguntas frecuentes.</p>
                @else
                    <div x-data="{ abierta: null }" class="border-t border-line">
                        @foreach($faqs as $faq)
                            <div class="border-b border-line">
                                <h3>
                                    <button type="button"
                                            id="faq-pregunta-{{ $faq->id_contenido }}"
                                            aria-controls="faq-respuesta-{{ $faq->id_contenido }}"
                                            :aria-expanded="abierta === {{ $faq->id_contenido }} ? 'true' : 'false'"
                                            @click="abierta = abierta === {{ $faq->id_contenido }} ? null : {{ $faq->id_contenido }}"
                                            class="w-full py-6 flex items-center justify-between gap-6 text-left font-display text-xl font-light text-carbon hover:text-wood-deep">
                                        <span>{{ $faq->titulo }}</span>
                                        <span aria-hidden="true" class="text-wood-deep text-2xl leading-none" x-text="abierta === {{ $faq->id_contenido }} ? '−' : '+'"></span>
                                    </button>
                                </h3>
                                <div id="faq-respuesta-{{ $faq->id_contenido }}"
                                     role="region" aria-labelledby="faq-pregunta-{{ $faq->id_contenido }}"
                                     x-show="abierta === {{ $faq->id_contenido }}" x-cloak style="display: none;"
                                     x-transition.opacity>
                                    <p class="ig-lede pb-6">{{ $faq->cuerpo }}</p>
                                    @if($faq->imagen_url)
                                        <img src="{{ $faq->imagen_url }}" alt="Imagen de apoyo para {{ $faq->titulo }}"
                                             loading="lazy" class="max-w-full max-h-80 object-contain mb-6">
                                    @elseif($faq->video_url)
                                        <video controls preload="metadata" playsinline class="w-full max-h-80 bg-carbon mb-6" aria-label="Video de apoyo para {{ $faq->titulo }}">
                                            <source src="{{ $faq->video_url }}" type="video/mp4">
                                        </video>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section id="contacto" class="ig-section bg-paper border-t border-line ig-anchor">
        <div class="ig-container">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24">
                <div class="ig-reveal">
                    <p class="ig-eyebrow mb-5">Escríbanos</p>
                    <h2 class="ig-h-section mb-7">Cuéntenos su proyecto</h2>

                    @if(session('contacto_success'))
                        <div class="ig-badge ig-badge-ok mb-8 !py-3 !px-4 !text-xs">
                            {{ session('contacto_success') }}
                        </div>
                    @endif

                    <p class="ig-lede mb-12">
                        Ya sea una gran obra industrial, galpones de acopio, o un conjunto
                        habitacional, nuestro equipo comercial se contactará con usted a la brevedad.
                    </p>

                    <p class="border-y border-line py-4 text-mute-deep font-light text-sm tracking-wide">
                        Complete el formulario para enviar su consulta al equipo comercial.
                    </p>
                </div>

                <div class="bg-surface p-8 md:p-12 border border-line ig-reveal ig-d2"
                     x-data="formularioContacto()">
                    <form x-ref="form" action="/contacto" method="POST" class="space-y-8"
                          @submit.prevent="pedirConfirmacion()">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div>
                                <label for="c-nombre" class="ig-label">Nombre</label>
                                <input id="c-nombre" type="text" name="nombre" x-model="campos.nombre" value="{{ old('nombre') }}" maxlength="80" pattern="[\p{L}\s]+" title="El nombre solo puede contener letras y espacios."
                                       class="ig-input @error('nombre') ig-input-invalid @enderror" required>
                                @error('nombre')<p class="ig-error"><i data-lucide="alert-circle" class="h-3.5 w-3.5 mt-0.5"></i>{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="c-apellido" class="ig-label">Apellidos</label>
                                <input id="c-apellido" type="text" name="apellido" x-model="campos.apellido" value="{{ old('apellido') }}" maxlength="80" pattern="[\p{L}\s]+" title="El apellido solo puede contener letras y espacios."
                                       class="ig-input @error('apellido') ig-input-invalid @enderror">
                                @error('apellido')<p class="ig-error"><i data-lucide="alert-circle" class="h-3.5 w-3.5 mt-0.5"></i>{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label for="c-email" class="ig-label">Correo electrónico</label>
                            <input id="c-email" type="email" name="email" x-model="campos.email" value="{{ old('email') }}"
                                   class="ig-input @error('email') ig-input-invalid @enderror" required>
                            @error('email')<p class="ig-error"><i data-lucide="alert-circle" class="h-3.5 w-3.5 mt-0.5"></i>{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="c-mensaje" class="ig-label">Mensaje</label>
                            <textarea id="c-mensaje" name="mensaje" rows="4" x-model="campos.mensaje" minlength="10" maxlength="1000"
                                      class="ig-textarea resize-none @error('mensaje') ig-input-invalid @enderror" required>{{ old('mensaje') }}</textarea>
                            @error('mensaje')<p class="ig-error"><i data-lucide="alert-circle" class="h-3.5 w-3.5 mt-0.5"></i>{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <div class="flex items-start gap-3 p-2 -m-2 transition-shadow duration-300"
                                 :class="resaltarTerminos ? 'ring-1 ring-[#b4403f] rounded-none' : ''">
                                <input type="checkbox" id="acepta_terminos" name="acepta_terminos" value="1"
                                       x-model="aceptaTerminos" class="mt-1 accent-[#111315]">
                                <label for="acepta_terminos" class="text-sm font-light text-mute-deep">
                                    Acepto los
                                    <a href="{{ route('terminos') }}" target="_blank" rel="noopener"
                                       class="underline hover:text-wood-deep transition-colors">Términos y Condiciones</a>
                                </label>
                            </div>
                            @error('acepta_terminos')<p class="ig-error"><i data-lucide="alert-circle" class="h-3.5 w-3.5 mt-0.5"></i>{{ $message }}</p>@enderror
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button type="button" @click="limpiar()" :disabled="enviando"
                                    class="ig-btn ig-btn-secondary sm:w-44 disabled:opacity-40">
                                Limpiar
                            </button>
                            <button type="submit" :disabled="!aceptaTerminos || enviando"
                                    class="ig-btn ig-btn-primary flex-1 disabled:opacity-40 disabled:cursor-not-allowed">
                                Enviar mensaje
                            </button>
                        </div>
                        <p x-show="!aceptaTerminos" style="display:none;"
                           class="ig-meta !text-xs">
                            Debe aceptar los Términos y Condiciones para habilitar el envío.
                        </p>
                    </form>

                    <div x-show="confirmando" x-cloak style="display:none;"
                         class="fixed inset-0 z-[80] flex items-center justify-center px-4"
                         @keydown.escape.window="cancelarEnvio()">
                        <div class="absolute inset-0 bg-carbon/70 backdrop-blur-[2px]" @click="cancelarEnvio()"></div>
                        <div class="relative bg-surface w-full max-w-md p-9 border border-line"
                             role="dialog" aria-modal="true" aria-labelledby="confirmar-titulo"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-3"
                             x-transition:enter-end="opacity-100 translate-y-0">
                            <h3 id="confirmar-titulo" class="font-display text-xl font-light tracking-tight mb-3">
                                ¿Enviar su consulta?
                            </h3>
                            <p class="text-mute text-sm font-light leading-relaxed mb-8">
                                Revisaremos su mensaje y nuestro equipo comercial se contactará al correo
                                <strong class="font-medium text-carbon" x-text="campos.email"></strong>.
                            </p>
                            <div class="flex gap-3">
                                <button type="button" @click="cancelarEnvio()"
                                        class="ig-btn ig-btn-secondary flex-1">Cancelar</button>
                                <button type="button" @click="confirmarEnvio()"
                                        class="ig-btn ig-btn-primary flex-1">Sí, enviar</button>
                            </div>
                        </div>
                    </div>

                    @if(session('consulta_id'))
                        <div x-data="{ exito: true }" x-show="exito" style="display:none;"
                             class="fixed inset-0 z-[80] flex items-center justify-center px-4"
                             @keydown.escape.window="exito = false">
                            <div class="absolute inset-0 bg-carbon/70 backdrop-blur-[2px]" @click="exito = false"></div>
                            <div class="relative bg-surface w-full max-w-md p-9 text-center border border-line"
                                 role="dialog" aria-modal="true"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-y-3"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <div class="mx-auto w-14 h-14 border border-line-strong flex items-center justify-center mb-6 bg-paper-deep">
                                    <i data-lucide="check" class="h-6 w-6 text-wood-deep"></i>
                                </div>
                                <h3 class="font-display text-xl font-light tracking-tight mb-3">Consulta enviada</h3>
                                <p class="text-mute text-sm font-light mb-2 leading-relaxed">{{ session('contacto_success') }}</p>
                                <p class="ig-meta mb-8">N° de seguimiento: {{ session('consulta_id') }}</p>
                                <button type="button" @click="exito = false" class="ig-btn ig-btn-primary w-full">
                                    Entendido
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <script>
      function fasesIndustriales() {
        return {
          activa: 0,
          imagenAbierta: null,
          disparador: null,
          overflowAnterior: '',

          abrirImagen(boton) {
            if (!boton.dataset.src) return;
            this.disparador = boton;
            this.imagenAbierta = {
              src: boton.dataset.src,
              descripcion: boton.dataset.descripcion,
              alt: boton.dataset.alt,
            };
            this.overflowAnterior = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.cerrarImagen.focus());
          },

          cerrarImagen() {
            if (!this.imagenAbierta) return;
            this.imagenAbierta = null;
            document.body.style.overflow = this.overflowAnterior;
            this.$nextTick(() => this.disparador?.focus());
          },
        };
      }

      function carruselColaboradores(total) {
        return {
          actual: 0,
          total: total,

          ir(indice) {
            if (indice === this.actual) {
              return;
            }
            this.actual = indice;
          },

          anterior() {
            this.ir(Math.max(0, this.actual - 1));
          },

          siguiente() {
            this.ir(Math.min(this.total - 1, this.actual + 1));
          },
        };
      }

      function formularioContacto() {
        return {
          campos: { nombre: @js(old('nombre', '')), apellido: @js(old('apellido', '')), email: @js(old('email', '')), mensaje: @js(old('mensaje', '')) },
          aceptaTerminos: @js((bool) old('acepta_terminos')),
          resaltarTerminos: false,
          confirmando: false,
          enviando: false,

          init() {
            if (@js($errors->hasAny(['nombre', 'apellido', 'email', 'mensaje', 'acepta_terminos']))) {
              this.$nextTick(() => document.getElementById('contacto')?.scrollIntoView());
            }
          },

          pedirConfirmacion() {
            if (!this.aceptaTerminos) {
              this.resaltarTerminos = true;
              setTimeout(() => (this.resaltarTerminos = false), 2500);
              return;
            }

            if (!this.$refs.form.checkValidity()) {
              this.$refs.form.reportValidity();
              return;
            }
            this.confirmando = true;
          },

          cancelarEnvio() {
            this.confirmando = false;
          },

          confirmarEnvio() {
            this.enviando = true;
            this.confirmando = false;
            this.$refs.form.submit();
          },

          limpiar() {
            if (this.enviando) return;

            this.campos = { nombre: '', apellido: '', email: '', mensaje: '' };
            this.aceptaTerminos = false;
          },
        };
      }
    </script>
</x-app-layout>
