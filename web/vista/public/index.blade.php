<x-app-layout>
    <div class="w-full">
      <section class="relative bg-[#4a504c] text-white overflow-hidden flex items-center min-h-[500px]">
        <div class="absolute inset-0 bg-gradient-to-r from-[#3a413d] to-[#7a7f79] opacity-80 mix-blend-multiply"></div>
        <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-white/20 to-transparent"></div>

        <div class="relative max-w-[1200px] mx-auto px-4 lg:px-8 py-20 md:py-32 w-full">
          <div class="max-w-2xl">
            <p class="text-[#c66f4b] font-bold text-xs tracking-[0.2em] uppercase mb-5 flex items-center">
              <span class="text-[#c66f4b] mr-2 text-lg leading-none">+</span>
              CONSTRUCCIÓN INDUSTRIALIZADA
            </p>
            <h1 class="text-[40px] md:text-[56px] font-bold leading-[1.1] mb-6 tracking-tight">
              Viviendas y galpones en madera, fabricados en serie
            </h1>

            @php $aniosExperiencia = now()->year - 1994; @endphp
            <p class="text-[#c66f4b] font-bold text-sm tracking-wide mb-6 flex items-center">
              <i data-lucide="award" class="w-5 h-5 mr-2"></i>
              {{ $aniosExperiencia }} años de experiencia — desde 1994
            </p>

            <p class="hidden md:block text-lg text-gray-200 mb-10 max-w-xl font-medium">
              Producimos volumen para constructoras e inmobiliarias, con la calidad de cada unidad y certificaciones que suman valor.
            </p>
            <p class="md:hidden text-lg text-gray-200 mb-10 font-medium">
              Producimos volumen para constructoras e inmobiliarias.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
              <a href="/proyectos" class="text-center bg-[#c66f4b] text-white px-8 py-3.5 rounded text-sm font-semibold hover:bg-[#b05f3e] transition-colors">
                Ver proyectos ejecutados
              </a>
              <a href="/producto" class="text-center hidden md:inline-block bg-transparent border border-white text-white px-8 py-3.5 rounded text-sm font-semibold hover:bg-white hover:text-[#1a1a1a] transition-colors">
                Nuestras líneas de producción
              </a>
            </div>
          </div>
        </div>
      </section>

      <section id="productos" class="py-24 bg-white scroll-mt-[88px]">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-14 gap-4">
            <div>
              <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">QUÉ HACEMOS</p>
              <h2 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] tracking-tight">Tres líneas de producción</h2>
            </div>
            <a href="/producto" class="text-[#28533c] font-semibold flex items-center text-sm hover:underline">
              Ver catálogo completo <i data-lucide="arrow-right" class="ml-1 w-4 h-4"></i>
            </a>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <a href="/producto" class="group cursor-pointer flex flex-col h-full block">
              <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[220px] rounded-t-lg mb-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">FOTOGRAFÍA</div>
              <div class="border border-t-0 border-[#e8e6df] p-8 rounded-b-lg flex-grow flex flex-col">
                <h3 class="text-[22px] font-bold text-[#1a1a1a] mb-3 leading-snug">Vivienda industrializada</h3>
                <p class="text-[#666666] mb-8 leading-relaxed flex-grow">Paneles y volumetría prefabricada para conjuntos habitacionales.</p>
                <span class="text-[#28533c] font-bold text-sm flex items-center group-hover:underline mt-auto">
                  Ver línea <i data-lucide="arrow-right" class="ml-1 w-4 h-4"></i>
                </span>
              </div>
            </a>

            <a href="/producto" class="group cursor-pointer flex flex-col h-full block">
              <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[220px] rounded-t-lg mb-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">FOTOGRAFÍA</div>
              <div class="border border-t-0 border-[#e8e6df] p-8 rounded-b-lg flex-grow flex flex-col">
                <h3 class="text-[22px] font-bold text-[#1a1a1a] mb-3 leading-snug">Pabellones y galpones</h3>
                <p class="text-[#666666] mb-8 leading-relaxed flex-grow">Estructuras de gran luz para uso industrial y agroindustrial.</p>
                <span class="text-[#28533c] font-bold text-sm flex items-center group-hover:underline mt-auto">
                  Ver línea <i data-lucide="arrow-right" class="ml-1 w-4 h-4"></i>
                </span>
              </div>
            </a>

            <a href="/producto" class="group cursor-pointer flex flex-col h-full block">
              <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[220px] rounded-t-lg mb-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">FOTOGRAFÍA</div>
              <div class="border border-t-0 border-[#e8e6df] p-8 rounded-b-lg flex-grow flex flex-col">
                <h3 class="text-[22px] font-bold text-[#1a1a1a] mb-3 leading-snug">Terminaciones y servicios</h3>
                <p class="text-[#666666] mb-8 leading-relaxed flex-grow">Escaleras prefabricadas, cerchas y elementos a medida.</p>
                <span class="text-[#28533c] font-bold text-sm flex items-center group-hover:underline mt-auto">
                  Ver línea <i data-lucide="arrow-right" class="ml-1 w-4 h-4"></i>
                </span>
              </div>
            </a>
          </div>
        </div>
      </section>

      <section class="py-24 bg-[#f5f3ec]">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
          <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">NUESTRO PROCESO</p>
          <h2 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] mb-5 max-w-2xl tracking-tight">
            De la madera en bruto al elemento montado
          </h2>
          <p class="hidden md:block text-[#666666] mb-14 max-w-3xl leading-relaxed text-lg">
            Cada etapa es un proceso de trabajo con altos estándares que nos permite asegurar la calidad mediante el control de un proceso de línea de gran volumen.
          </p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-10 md:mt-0">
            <div>
              <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[180px] rounded-lg mb-6" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">FOTOGRAFÍA DE LA ETAPA</div>
              <div class="flex items-start mb-3 gap-4">
                <span class="text-[28px] font-bold text-[#c66f4b] leading-none mt-1">01</span>
                <div>
                  <h3 class="text-xl font-bold text-[#1a1a1a] mb-2">Descortezado</h3>
                  <p class="text-[#666666] leading-relaxed">Preparación de la madera en rollizos.</p>
                </div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[180px] rounded-lg mb-6" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">FOTOGRAFÍA DE LA ETAPA</div>
              <div class="flex items-start mb-3 gap-4">
                <span class="text-[28px] font-bold text-[#c66f4b] leading-none mt-1">02</span>
                <div>
                  <h3 class="text-xl font-bold text-[#1a1a1a] mb-2">Impregnación vacío-presión</h3>
                  <p class="text-[#666666] leading-relaxed">Tratamiento con preservantes para durabilidad estructural.</p>
                </div>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[180px] rounded-lg mb-6" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">FOTOGRAFÍA DE LA ETAPA</div>
              <div class="flex items-start mb-3 gap-4">
                <span class="text-[28px] font-bold text-[#c66f4b] leading-none mt-1">03</span>
                <div>
                  <h3 class="text-xl font-bold text-[#1a1a1a] mb-2">Ensamblado</h3>
                  <p class="text-[#666666] leading-relaxed">Armado de cerchas, paneles y secciones.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="proyectos" class="py-24 bg-white scroll-mt-[88px]">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-14 gap-4">
            <div>
              <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">OBRAS EN TERRENO</p>
              <h2 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] tracking-tight">Proyectos recientes</h2>
            </div>
            <a href="/proyectos" class="text-[#28533c] font-semibold flex items-center text-sm hover:underline">
              Ver galería completa <i data-lucide="arrow-right" class="ml-1 w-4 h-4"></i>
            </a>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @forelse($proyectos_recientes as $proyecto)
            <div class="border border-[#e8e6df] rounded-lg overflow-hidden flex flex-col bg-white">
              @if($proyecto->imagenes->isNotEmpty())
                  <img src="{{ Storage::url($proyecto->imagenes->first()->imagen) }}" alt="{{ $proyecto->nombre_obra }}" class="w-full h-[240px] object-cover">
              @else
                  <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[240px]" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">SIN IMAGEN</div>
              @endif
              <div class="p-6 flex-grow flex flex-col">
                <span class="inline-block bg-[#eaf0ec] text-[#28533c] text-[11px] font-bold px-3 py-1 rounded-full w-max mb-5 uppercase tracking-wide">
                  {{ $proyecto->categoria }}
                </span>
                <h3 class="text-xl font-bold text-[#1a1a1a] mb-8">{{ $proyecto->nombre_obra }}</h3>
                <div class="mt-auto flex justify-between items-center text-sm text-[#666666]">
                  <span class="flex items-center">
                    <i data-lucide="map-pin" class="w-4 h-4 mr-1.5 opacity-60"></i> {{ $proyecto->ubicacion_geografica }}
                  </span>
                  <span class="font-medium">{{ $proyecto->anio_ejecucion }}</span>
                </div>
              </div>
            </div>
            @empty
              <p class="text-gray-500">Pronto publicaremos nuestros proyectos recientes.</p>
            @endforelse
          </div>
        </div>
      </section>

      <section id="certificaciones" class="py-24 bg-[#29543d] text-white scroll-mt-[88px]">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8">
            <div class="lg:col-span-4 pr-0 lg:pr-8">
              <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">MARCO TÉCNICO</p>
              <h2 class="text-3xl md:text-4xl font-bold mb-6 tracking-tight">Cumplimos la norma chilena</h2>
              <p class="text-[#d8e3dc] mb-10 leading-relaxed">
                Nuestra madera y procesos están certificados para garantizar resistencia y calidad. Cada obra cuenta con el respaldo que la ley exige.
              </p>
              <a href="/certificaciones" class="inline-block bg-white text-[#29543d] px-8 py-3.5 rounded text-sm font-semibold hover:bg-gray-50 transition-colors w-max">
                Ver certificados
              </a>
            </div>
            <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-3 gap-6">
              @forelse($certificados as $cert)
              <div class="bg-[#3a614b] p-8 rounded-lg flex flex-col justify-between">
                <div>
                  <div class="w-10 h-10 bg-white rounded flex items-center justify-center mb-6 overflow-hidden">
                    @if($cert->imagen)
                      <img src="{{ Storage::url($cert->imagen) }}" class="w-full h-full object-contain p-1">
                    @else
                      <div class="w-3 h-3 border-2 border-[#29543d] rounded-sm"></div>
                    @endif
                  </div>
                  <h3 class="text-xl font-bold mb-3">{{ $cert->nombre }}</h3>
                  <p class="text-sm text-[#d8e3dc] leading-relaxed">{{ $cert->organismo }}</p>
                </div>
              </div>
              @empty
              <p class="text-[#d8e3dc] col-span-3">Certificaciones en proceso de actualización.</p>
              @endforelse
            </div>
          </div>
        </div>
      </section>

      <section class="py-24 bg-white text-center">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
          <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">PROVEEDORES</p>
          <h2 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] tracking-tight mb-14">Proveedores estratégicos</h2>
          @php $grupos = $proveedores->chunk(4); @endphp

          @if($grupos->isEmpty())
            <div class="text-center text-gray-400">Pronto publicaremos nuestros proveedores oficiales.</div>
          @else
            <div x-data="carruselColaboradores({{ $grupos->count() }})">
              <div class="relative">
                <div class="overflow-hidden">
                  <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${actual * 100}%)`">
                    @foreach($grupos as $grupo)
                      <div class="w-full shrink-0 grid grid-cols-2 md:grid-cols-4 gap-6">
                        @foreach($grupo as $prov)
                          <div class="flex items-center justify-center bg-white w-full h-[80px] rounded border overflow-hidden p-2 shadow-sm">
                            <img src="{{ Storage::url($prov->logotipo) }}" alt="{{ $prov->nombre_comercial }}" class="max-h-full max-w-full object-contain">
                          </div>
                        @endforeach
                      </div>
                    @endforeach
                  </div>
                </div>

                @if($grupos->count() > 1)
                  <button type="button" @click="anterior()" :disabled="actual === 0" aria-label="Ver colaboradores anteriores"
                          class="absolute top-1/2 -translate-y-1/2 -left-3 md:-left-8 z-10 h-10 w-10 rounded-full bg-white border border-[#e8e6df] shadow-sm flex items-center justify-center text-[#28533c] transition-colors hover:bg-[#28533c] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-[#28533c]">
                    <i data-lucide="chevron-left" class="h-5 w-5"></i>
                  </button>
                  <button type="button" @click="siguiente()" :disabled="actual === total - 1" aria-label="Ver siguientes colaboradores"
                          class="absolute top-1/2 -translate-y-1/2 -right-3 md:-right-8 z-10 h-10 w-10 rounded-full bg-white border border-[#e8e6df] shadow-sm flex items-center justify-center text-[#28533c] transition-colors hover:bg-[#28533c] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-[#28533c]">
                    <i data-lucide="chevron-right" class="h-5 w-5"></i>
                  </button>
                @endif
              </div>

              @if($grupos->count() > 1)
                <div class="mt-10 flex items-center justify-center gap-2">
                  @foreach($grupos as $i => $grupo)
                    <button type="button" @click="ir({{ $i }})"
                            :aria-current="actual === {{ $i }} ? 'true' : 'false'"
                            aria-label="Ver grupo {{ $i + 1 }} de {{ $grupos->count() }}"
                            class="h-2.5 rounded-full transition-all duration-300"
                            :class="actual === {{ $i }} ? 'w-8 bg-[#28533c]' : 'w-2.5 bg-[#d9d9d9] hover:bg-[#b5b5b5]'"></button>
                  @endforeach
                </div>
              @endif
            </div>
          @endif
        </div>
      </section>

      <section id="contacto" class="py-24 bg-white border-t border-gray-100 scroll-mt-[88px]">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
          <div class="bg-[#f5f3ec] rounded-2xl p-8 md:p-14">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
              <div>
                <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">ESCRÍBANOS</p>
                <h2 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] mb-6 tracking-tight">Cuéntenos su proyecto</h2>

                @if(session('contacto_success'))
                    <div class="bg-green-50 text-green-700 p-4 rounded-md mb-6 font-medium border border-green-200">
                        {{ session('contacto_success') }}
                    </div>
                @endif
                <p class="text-[#666666] mb-10 leading-relaxed text-lg">
                  Ya sea una gran obra industrial, galpones de acopio, o un conjunto habitacional, nuestro equipo comercial se contactará con usted a la brevedad.
                </p>
                <div class="space-y-4">
                  <div class="flex items-center text-[#1a1a1a] font-medium">
                    <i data-lucide="check" class="w-5 h-5 text-[#28533c] mr-3"></i> [CORREO ELECTRÓNICO]
                  </div>
                  <div class="flex items-center text-[#1a1a1a] font-medium">
                    <i data-lucide="check" class="w-5 h-5 text-[#28533c] mr-3"></i> [TELÉFONO]
                  </div>
                  <div class="flex items-center text-[#1a1a1a] font-medium">
                    <i data-lucide="check" class="w-5 h-5 text-[#28533c] mr-3"></i> [DIRECCIÓN]
                  </div>
                </div>
              </div>

              <div class="bg-transparent" x-data="formularioContacto()">
                <form x-ref="form" action="/contacto" method="POST" class="space-y-6"
                      @submit.prevent="pedirConfirmacion()">
                  @csrf
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                      <label for="c-nombre" class="block text-sm font-bold text-[#1a1a1a] mb-2">Nombre</label>
                      <input id="c-nombre" type="text" name="nombre" x-model="campos.nombre" value="{{ old('nombre') }}"
                             class="w-full rounded px-4 py-3.5 outline-none shadow-sm focus:ring-2 @error('nombre') border-2 border-red-400 focus:ring-red-300 @else border-0 focus:ring-[#28533c] @enderror" required>
                      @error('nombre')<p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>@enderror
                    </div>
                    <div>
                      <label for="c-apellido" class="block text-sm font-bold text-[#1a1a1a] mb-2">Apellidos</label>
                      <input id="c-apellido" type="text" name="apellido" x-model="campos.apellido" value="{{ old('apellido') }}"
                             class="w-full rounded px-4 py-3.5 outline-none shadow-sm focus:ring-2 @error('apellido') border-2 border-red-400 focus:ring-red-300 @else border-0 focus:ring-[#28533c] @enderror">
                      @error('apellido')<p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>@enderror
                    </div>
                  </div>
                  <div>
                    <label for="c-email" class="block text-sm font-bold text-[#1a1a1a] mb-2">Correo electrónico</label>
                    <input id="c-email" type="email" name="email" x-model="campos.email" value="{{ old('email') }}"
                           class="w-full rounded px-4 py-3.5 outline-none shadow-sm focus:ring-2 @error('email') border-2 border-red-400 focus:ring-red-300 @else border-0 focus:ring-[#28533c] @enderror" required>
                    @error('email')<p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>@enderror
                  </div>
                  <div>
                    <label for="c-mensaje" class="block text-sm font-bold text-[#1a1a1a] mb-2">Mensaje</label>
                    <textarea id="c-mensaje" name="mensaje" rows="4" x-model="campos.mensaje"
                              class="w-full rounded px-4 py-3.5 outline-none shadow-sm focus:ring-2 @error('mensaje') border-2 border-red-400 focus:ring-red-300 @else border-0 focus:ring-[#28533c] @enderror" required>{{ old('mensaje') }}</textarea>
                    @error('mensaje')<p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>@enderror
                  </div>

                  <div>
                    <div class="flex items-center"
                         :class="resaltarTerminos ? 'ring-2 ring-red-400 rounded p-2 -m-2' : ''">
                      <input type="checkbox" id="acepta_terminos" name="acepta_terminos" value="1"
                             x-model="aceptaTerminos" class="mr-2">
                      <label for="acepta_terminos" class="text-sm">
                        Acepto los <a href="{{ route('terminos') }}" target="_blank" rel="noopener" class="underline">Términos y Condiciones</a>
                      </label>
                    </div>
                    @error('acepta_terminos')<p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>@enderror
                  </div>

                  <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" @click="limpiar()" :disabled="enviando"
                            class="sm:w-40 border border-[#28533c] text-[#28533c] px-6 py-4 rounded text-sm font-semibold hover:bg-[#eaf0ec] transition-colors disabled:opacity-50">
                      Limpiar
                    </button>
                    <button type="submit" :disabled="!aceptaTerminos || enviando"
                            class="flex-1 bg-[#28533c] text-white px-8 py-4 rounded text-sm font-semibold hover:bg-[#1e402e] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                      Enviar mensaje
                    </button>
                  </div>
                  <p x-show="!aceptaTerminos" style="display:none;" class="text-xs text-[#666666]">
                    Debe aceptar los Términos y Condiciones para habilitar el envío.
                  </p>
                </form>

                <div x-show="confirmando" style="display:none;"
                     class="fixed inset-0 z-[80] flex items-center justify-center px-4"
                     @keydown.escape.window="cancelarEnvio()">
                  <div class="absolute inset-0 bg-black/60" @click="cancelarEnvio()"></div>
                  <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-8" role="dialog" aria-modal="true">
                    <h3 class="text-xl font-bold text-[#1a1a1a] mb-3">¿Enviar su consulta?</h3>
                    <p class="text-sm text-[#666666] mb-6 leading-relaxed">
                      Revisaremos su mensaje y nuestro equipo comercial se contactará al correo
                      <strong class="text-[#1a1a1a]" x-text="campos.email"></strong>.
                    </p>
                    <div class="flex gap-3">
                      <button type="button" @click="cancelarEnvio()"
                              class="flex-1 border border-gray-300 text-[#1a1a1a] px-5 py-3 rounded text-sm font-semibold hover:bg-gray-50">
                        Cancelar
                      </button>
                      <button type="button" @click="confirmarEnvio()"
                              class="flex-1 bg-[#28533c] text-white px-5 py-3 rounded text-sm font-semibold hover:bg-[#1e402e]">
                        Sí, enviar
                      </button>
                    </div>
                  </div>
                </div>

                @if(session('consulta_id'))
                  <div x-data="{ exito: true }" x-show="exito" style="display:none;"
                       class="fixed inset-0 z-[80] flex items-center justify-center px-4"
                       @keydown.escape.window="exito = false">
                    <div class="absolute inset-0 bg-black/60" @click="exito = false"></div>
                    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-8 text-center" role="dialog" aria-modal="true">
                      <div class="mx-auto w-14 h-14 rounded-full bg-[#eaf0ec] flex items-center justify-center mb-5">
                        <i data-lucide="check" class="h-7 w-7 text-[#28533c]"></i>
                      </div>
                      <h3 class="text-xl font-bold text-[#1a1a1a] mb-3">Consulta enviada</h3>
                      <p class="text-sm text-[#666666] mb-2 leading-relaxed">{{ session('contacto_success') }}</p>
                      <p class="text-xs text-[#99968f] mb-6">N° de seguimiento: {{ session('consulta_id') }}</p>
                      <button type="button" @click="exito = false"
                              class="w-full bg-[#28533c] text-white px-5 py-3 rounded text-sm font-semibold hover:bg-[#1e402e]">
                        Entendido
                      </button>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

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
