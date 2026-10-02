@php
    $aniosExperiencia = now()->year - 1994;
    $primeraObra = $proyectos_recientes->first();
    $heroImagen = $primeraObra?->imagenes->first();
@endphp

<x-app-layout titulo="Ingecon — Industrialización de la madera y construcción prefabricada">

    {{-- ============================ HERO ============================ --}}
    <section data-ig-hero-overlay
             class="relative min-h-[100svh] flex items-end bg-carbon text-white overflow-hidden">
        @if($heroImagen)
            {{-- Imagen panorámica fija: es el LCP del sitio, se carga eager y con prioridad alta. --}}
            <img src="{{ asset('img/hero-panoramica.jpg') }}"
                 alt="Izaje con grúa de una cercha de madera laminada unida con placas conectoras metálicas, sobre una losa en construcción"
                 fetchpriority="high" width="1600" height="686"
                 class="ig-hero-shot absolute inset-0 w-full h-full object-cover opacity-55">
        @else
            {{-- Sin fotografía cargada todavía: plano de taller, no hueco gris. --}}
            <div class="absolute inset-0 ig-blueprint opacity-70"></div>
            <div class="absolute inset-0"
                 style="background:
                        radial-gradient(120% 90% at 78% 12%, rgba(184,138,88,0.26) 0%, rgba(184,138,88,0) 60%),
                        repeating-linear-gradient(90deg, rgba(255,255,255,0.05) 0 1px, transparent 1px 96px);"></div>
            {{-- Cercha simbólica: geometría estructural dibujada, sin inventar una obra. --}}
            <svg class="absolute right-0 bottom-0 h-[62%] w-[58%] text-white/10 hidden md:block"
                 viewBox="0 0 400 260" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                <path d="M10 250h380M10 250 200 20l190 230M60 250V170l40-46M340 250v-80l-40-46M100 250V120l100-100 100 100v130"/>
                <path d="M100 120h200M60 170h280M140 250l60-60 60 60" stroke-dasharray="4 6"/>
            </svg>
        @endif

        <div class="absolute inset-0"
             style="background: linear-gradient(to bottom, rgba(17,19,21,0.86) 0%, rgba(17,19,21,0.55) 45%, rgba(17,19,21,0.94) 100%);"></div>

        <div class="relative z-10 ig-container w-full pt-40 pb-28">
            <div class="max-w-3xl">
                <p class="ig-eyebrow ig-eyebrow-on-dark ig-reveal mb-7">Construcción industrializada</p>

                <h1 class="ig-h-display ig-reveal ig-d1 mb-8 !text-white">
                    Viviendas y galpones en madera,<br class="hidden md:block">
                    <span class="font-normal text-wood-light">fabricados en serie</span>
                </h1>

                <p class="ig-meta ig-reveal ig-d2 mb-7 !text-wood-light">
                    {{ $aniosExperiencia }} años de experiencia — desde 1994
                </p>

                <p class="ig-lede ig-reveal ig-d3 mb-12 max-w-xl !text-white/70">
                    Producimos volumen para constructoras e inmobiliarias, con la calidad de
                    cada unidad y certificaciones que suman valor.
                </p>

                <div class="ig-reveal ig-d4 flex flex-col sm:flex-row gap-4">
                    <a href="/proyectos" class="ig-btn ig-btn-hero">
                        <span class="ig-btn-label">
                            Ver proyectos ejecutados
                            <i data-lucide="arrow-right" class="ig-btn-arrow h-4 w-4"></i>
                        </span>
                    </a>
                    <a href="/producto" class="ig-btn ig-btn-hero !border-white/20 !text-white/70">
                        <span class="ig-btn-label">Nuestras líneas de producción</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center gap-3">
            <span class="ig-meta !text-white/60">Scroll</span>
            <span class="ig-scroll-line"></span>
        </div>
    </section>

    {{-- ==================== LÍNEAS DE PRODUCCIÓN ==================== --}}
    <section id="productos" class="ig-section bg-surface ig-anchor">
        <div class="ig-container">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-16 lg:mb-20">
                <div class="ig-reveal max-w-2xl">
                    <p class="ig-eyebrow mb-4">Qué hacemos</p>
                    <h2 class="ig-h-section mb-5">Tres líneas de producción</h2>
                    <p class="ig-lede">
                        Cada línea resuelve un tramo distinto del ciclo: la envolvente,
                        la estructura de gran luz y el detalle final de obra.
                    </p>
                </div>
                <a href="/producto" class="ig-link-underline ig-reveal ig-d1 shrink-0">
                    Ver catálogo completo
                </a>
            </div>

            {{-- Registro editorial en lugar de tres cards idénticas. --}}
            <div class="border-t border-line">
                @foreach([
                    ['Vivienda industrializada', 'Paneles y volumetría prefabricada para conjuntos habitacionales.',
                     'img/linea-vivienda-industrializada.jpg',
                     'Fila de módulos habitacionales de dos pisos con revestimiento de madera, en plena etapa de montaje'],
                    ['Pabellones y galpones', 'Estructuras de gran luz para uso industrial y agroindustrial.',
                     'img/linea-pabellon-gran-luz.jpg',
                     'Exterior de un galpón de gran luz con pórticos de madera laminada a la vista y cierre en plancha'],
                    ['Terminaciones y servicios', 'Escaleras prefabricadas, cerchas y elementos a medida.',
                     'img/linea-terminaciones.jpg',
                     'Escalera prefabricada en madera con baranda de fierro negro, instalada en una vivienda terminada'],
                ] as $i => $linea)
                <a href="/producto"
                   class="group grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-8 items-baseline py-9 border-b border-line ig-reveal ig-d{{ $i + 1 }} transition-colors hover:bg-paper">
                    <span class="md:col-span-1 ig-meta !text-wood-deep">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="md:col-span-4 font-display text-2xl font-light tracking-tight text-carbon group-hover:text-wood-deep transition-colors">
                        {{ $linea[0] }}
                    </h3>
                    <p class="md:col-span-4 text-mute font-light leading-relaxed">{{ $linea[1] }}</p>
                    <span class="md:col-span-2 md:self-center ig-shot h-20 md:h-24 w-full">
                        <img src="{{ asset($linea[2]) }}" alt="{{ $linea[3] }}" loading="lazy"
                             width="1536" height="1024">
                    </span>
                    <span class="md:col-span-1 flex md:justify-end">
                        <i data-lucide="arrow-right" class="h-5 w-5 text-line-strong group-hover:text-wood group-hover:translate-x-1 transition-all duration-300"></i>
                    </span>
                </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ========================= PROCESO ========================= --}}
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-line">
                @foreach([
                    ['Descortezado', 'Preparación de la madera en rollizos.',
                     'img/proceso-descortezado.jpg',
                     'Rollizos de pino con corteza apilados al frente de un aserradero, con un camión cargado de trozas al fondo'],
                    ['Impregnación vacío-presión', 'Tratamiento con preservantes para durabilidad estructural.',
                     'img/proceso-impregnacion.jpg',
                     'Carro con madera tratada de tono verde saliendo del cilindro de un autoclave de impregnación'],
                    ['Ensamblado', 'Armado de cerchas, paneles y secciones.',
                     'img/proceso-ensamblado.jpg',
                     'Operario fijando con clavadora neumática una placa metálica en el nudo de una cercha sobre la mesa de ensamblado'],
                ] as $i => $etapa)
                <div class="bg-paper-deep p-8 lg:p-10 ig-reveal ig-d{{ $i + 1 }} group">
                    <div class="ig-shot w-full h-44 mb-8">
                        <img src="{{ asset($etapa[2]) }}" alt="{{ $etapa[3] }}" loading="lazy"
                             width="1448" height="1086">
                    </div>
                    <div class="flex items-baseline gap-4 mb-8">
                        <span class="font-display text-5xl font-extralight text-line-strong group-hover:text-wood transition-colors duration-500">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="ig-rule flex-grow origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500 bg-wood"></span>
                    </div>
                    <h3 class="font-display text-xl font-medium mb-3">{{ $etapa[0] }}</h3>
                    <p class="text-mute font-light leading-relaxed">{{ $etapa[1] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================== PROYECTOS ======================== --}}
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
                    {{-- Obra destacada --}}
                    <a href="/proyectos" class="lg:col-span-7 ig-shot h-[340px] md:h-[480px] ig-reveal">
                        @if($destacado->imagenes->isNotEmpty())
                            <img src="{{ Storage::url($destacado->imagenes->first()->imagen) }}"
                                 alt="{{ $destacado->nombre_obra }}" loading="lazy">
                        @else
                            <span class="ig-plate"><span class="ig-plate-label">Sin fotografía</span></span>
                        @endif
                        <span class="ig-shot-veil"></span>
                        <span class="absolute bottom-0 left-0 w-full p-8 md:p-12 flex flex-col justify-end">
                            <span class="ig-meta !text-wood-light mb-3">{{ $destacado->categoria }}</span>
                            <span class="font-display text-2xl md:text-4xl font-light tracking-tight text-white">
                                {{ $destacado->nombre_obra }}
                            </span>
                            <span class="ig-meta !text-white/60 mt-4">
                                {{ $destacado->ubicacion_geografica }} · {{ $destacado->anio_ejecucion }}
                            </span>
                        </span>
                    </a>

                    {{-- Obras secundarias --}}
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
                                <span class="ig-meta !text-wood-light mb-2 block">{{ $proyecto->categoria }}</span>
                                <span class="font-display text-xl font-light tracking-tight text-white block">
                                    {{ $proyecto->nombre_obra }}
                                </span>
                                <span class="ig-meta !text-white/60 mt-3 block">
                                    {{ $proyecto->ubicacion_geografica }} · {{ $proyecto->anio_ejecucion }}
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

                {{-- Obras restantes en registro tabular, sin repetir la card. --}}
                @if($proyectos_recientes->count() > 3)
                <div class="mt-14 border-t border-line">
                    @foreach($proyectos_recientes->slice(3) as $proyecto)
                    <a href="/proyectos"
                       class="group flex flex-wrap items-baseline gap-x-6 gap-y-2 py-5 border-b border-line ig-reveal">
                        <span class="font-display text-lg font-light text-carbon group-hover:text-wood-deep transition-colors">
                            {{ $proyecto->nombre_obra }}
                        </span>
                        <span class="ig-meta">{{ $proyecto->ubicacion_geografica }}</span>
                        <span class="ig-meta ml-auto">{{ $proyecto->anio_ejecucion }}</span>
                    </a>
                    @endforeach
                </div>
                @endif
            @endif
        </div>
    </section>

    {{-- ===================== CERTIFICACIONES ===================== --}}
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

    {{-- ======================= PROVEEDORES ======================= --}}
    <section class="ig-section bg-surface">
        <div class="ig-container">
            <div class="text-center mb-14 ig-reveal">
                <p class="ig-eyebrow justify-center mb-4">Proveedores</p>
                <h2 class="ig-h-section">Proveedores estratégicos</h2>
            </div>

            @php $grupos = $proveedores->chunk(4); @endphp

            @if($grupos->isEmpty())
                <div class="text-center ig-lede ig-reveal">Pronto publicaremos nuestros proveedores oficiales.</div>
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
                                            <div class="flex items-center justify-center bg-surface h-[120px] p-6 overflow-hidden">
                                                <img src="{{ Storage::url($prov->logotipo) }}"
                                                     alt="{{ $prov->nombre_comercial }}"
                                                     loading="lazy"
                                                     class="max-h-full max-w-full object-contain grayscale opacity-65 hover:grayscale-0 hover:opacity-100 transition-all duration-500">
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

    {{-- ======================== CONTACTO ======================== --}}
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

                    <ul class="space-y-px bg-line border-y border-line">
                        @foreach(['[CORREO ELECTRÓNICO]', '[TELÉFONO]', '[DIRECCIÓN]'] as $i => $dato)
                        <li class="flex items-center gap-4 bg-paper py-4 px-1">
                            <span class="ig-meta !text-wood-deep">{{ sprintf('%02d', $i + 1) }}</span>
                            <span class="text-mute-deep font-light text-sm tracking-wide">{{ $dato }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="bg-surface p-8 md:p-12 border border-line ig-reveal ig-d2"
                     x-data="formularioContacto()">
                    <form x-ref="form" action="/contacto" method="POST" class="space-y-8"
                          @submit.prevent="pedirConfirmacion()">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                            <div>
                                <label for="c-nombre" class="ig-label">Nombre</label>
                                <input id="c-nombre" type="text" name="nombre" x-model="campos.nombre" value="{{ old('nombre') }}"
                                       class="ig-input @error('nombre') ig-input-invalid @enderror" required>
                                @error('nombre')<p class="ig-error"><i data-lucide="alert-circle" class="h-3.5 w-3.5 mt-0.5"></i>{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="c-apellido" class="ig-label">Apellidos</label>
                                <input id="c-apellido" type="text" name="apellido" x-model="campos.apellido" value="{{ old('apellido') }}"
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
                            <textarea id="c-mensaje" name="mensaje" rows="4" x-model="campos.mensaje"
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

                    {{-- CU5.1 — confirmación de envío --}}
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

                    {{-- CU9.1 — éxito con N° de seguimiento --}}
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
          campos: { nombre: '', apellido: '', email: '', mensaje: '' },
          aceptaTerminos: false,
          resaltarTerminos: false,
          confirmando: false,
          enviando: false,

          init() {
            this.$nextTick(() => {
              const f = this.$refs.form;
              this.campos.nombre = f.nombre.value;
              this.campos.apellido = f.apellido.value;
              this.campos.email = f.email.value;
              this.campos.mensaje = f.mensaje.value;
            });
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
