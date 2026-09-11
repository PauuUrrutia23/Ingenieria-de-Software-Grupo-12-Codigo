<x-app-layout>
    <div class="w-full bg-[#f5f3ec] min-h-screen pb-24"
         x-data="galeriaProyectos()"
         @keydown.escape.window="cerrarFicha()">

      <div class="bg-white border-b border-gray-200">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-16">
          <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">OBRAS EJECUTADAS</p>
          <h1 class="text-4xl md:text-5xl font-bold text-[#1a1a1a] mb-6 tracking-tight">Nuestros proyectos</h1>
          <p class="text-lg text-[#666666] max-w-2xl leading-relaxed">
            Obras entregadas a constructoras, inmobiliarias y clientes industriales a lo largo del país.
          </p>
        </div>
      </div>

      <div class="max-w-[1200px] mx-auto px-4 lg:px-8 mt-12">
        <div class="bg-white p-6 rounded-lg border border-[#e8e6df] shadow-sm mb-8">
          <form id="filtros-proyectos" action="/proyectos" method="GET"
                @submit.prevent="aplicarFiltros()"
                class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
            <div class="md:col-span-5">
              <label for="filtro-q" class="block text-sm font-bold text-[#1a1a1a] mb-2">Buscar por nombre o ubicación</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <i data-lucide="search" class="h-5 w-5 text-gray-400"></i>
                </div>
                <input
                  id="filtro-q"
                  type="text"
                  name="q"
                  value="{{ request('q') }}"
                  placeholder="Ej. galpón, Los Ángeles"
                  class="w-full pl-11 border border-gray-300 rounded px-4 py-3 focus:ring-2 focus:ring-[#28533c] outline-none text-[#1a1a1a]"
                />
              </div>
            </div>
            <div class="md:col-span-3">
              <label for="filtro-categoria" class="block text-sm font-bold text-[#1a1a1a] mb-2">Línea de producto</label>
              <div class="relative">
                <select id="filtro-categoria" name="categoria" class="w-full appearance-none border border-gray-300 rounded pl-4 pr-10 py-3 focus:ring-2 focus:ring-[#28533c] outline-none bg-white text-[#1a1a1a]">
                  <option value="">Todas</option>
                  <option value="construccion" {{ request('categoria') == 'construccion' ? 'selected' : '' }}>Construcción</option>
                  <option value="industrial" {{ request('categoria') == 'industrial' ? 'selected' : '' }}>Industrial</option>
                  <option value="terminaciones" {{ request('categoria') == 'terminaciones' ? 'selected' : '' }}>Terminaciones</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="chevron-down" class="h-4 w-4 text-gray-500"></i>
                </div>
              </div>
            </div>
            <div class="md:col-span-2">
              <label for="filtro-region" class="block text-sm font-bold text-[#1a1a1a] mb-2">Región</label>
              <div class="relative">
                <select id="filtro-region" name="region" class="w-full appearance-none border border-gray-300 rounded pl-4 pr-10 py-3 focus:ring-2 focus:ring-[#28533c] outline-none bg-white text-[#1a1a1a]">
                  <option value="">Todas</option>
                  @if(isset($regiones))
                    @foreach($regiones as $r)
                      <option value="{{ $r }}" {{ request('region') == $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                  @endif
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                  <i data-lucide="chevron-down" class="h-4 w-4 text-gray-500"></i>
                </div>
              </div>
            </div>
            <div class="md:col-span-2">
              <button type="submit" class="w-full bg-[#28533c] text-white px-4 py-3 rounded text-sm font-semibold hover:bg-[#1e402e] transition-colors disabled:opacity-60"
                      :disabled="cargando">
                <span x-show="!cargando">Aplicar Filtros</span>
                <span x-show="cargando" style="display:none;">Buscando…</span>
              </button>
            </div>
          </form>

          <p x-show="aviso" style="display:none;" x-text="aviso"
             class="mt-4 text-sm font-medium text-[#c66f4b]"></p>
        </div>

        <div id="resultados-proyectos" :class="cargando ? 'opacity-50 transition-opacity' : ''">
          @include('public.partials.proyectos-grid')
        </div>
      </div>

      <div x-show="fichaAbierta" style="display:none;"
           class="fixed inset-0 z-[80] flex items-center justify-center px-4 py-8">
        <div class="absolute inset-0 bg-black/60" @click="cerrarFicha()"></div>

        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-full overflow-y-auto"
             role="dialog" aria-modal="true" aria-labelledby="ficha-titulo">

          <button type="button" @click="cerrarFicha()" aria-label="Cerrar ficha técnica"
                  class="absolute top-4 right-4 z-10 bg-white/90 rounded-full p-2 text-gray-500 hover:text-[#1a1a1a] shadow">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>

          <template x-if="ficha.imagenes && ficha.imagenes.length">
            <img :src="ficha.imagenes[0]" :alt="ficha.nombre" class="w-full h-[280px] object-cover rounded-t-xl">
          </template>

          <div class="p-8">
            <span class="inline-block bg-[#eaf0ec] text-[#28533c] text-[11px] font-bold px-3 py-1 rounded-full w-max mb-4 uppercase tracking-wide"
                  x-text="ficha.categoria"></span>

            <h2 id="ficha-titulo" class="text-3xl font-bold text-[#1a1a1a] mb-2 tracking-tight" x-text="ficha.nombre"></h2>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-[#666666] mb-6 pb-6 border-b border-[#e8e6df]">
              <span class="flex items-center">
                <i data-lucide="map-pin" class="w-4 h-4 mr-1.5 opacity-60"></i>
                <span x-text="ficha.ubicacion"></span>
              </span>
              <span class="flex items-center">
                <i data-lucide="calendar" class="w-4 h-4 mr-1.5 opacity-60"></i>
                <span x-text="ficha.anio"></span>
              </span>
            </div>

            <h3 class="text-sm font-bold text-[#1a1a1a] uppercase tracking-wide mb-3">Descripción técnica</h3>
            <p class="text-[#4a4a4a] leading-relaxed whitespace-pre-wrap mb-8" x-text="ficha.descripcion"></p>

            <template x-if="ficha.imagenes && ficha.imagenes.length > 1">
              <div>
                <h3 class="text-sm font-bold text-[#1a1a1a] uppercase tracking-wide mb-3">Registro fotográfico</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <template x-for="(img, i) in ficha.imagenes.slice(1)" :key="i">
                    <img :src="img" :alt="ficha.nombre" class="w-full h-28 object-cover rounded border border-[#e8e6df]"
                         onerror="this.style.display='none'">
                  </template>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    <script>
      function galeriaProyectos() {
        return {
          cargando: false,
          aviso: '',
          fichaAbierta: false,
          ficha: {},

          _peticion: null,

          init() {
            document.getElementById('resultados-proyectos')
              .addEventListener('click', (e) => {
                const card = e.target.closest('.js-abrir-ficha');
                if (card) this.abrirFicha(JSON.parse(card.dataset.proyecto));
              });
          },

          abrirFicha(datos) {
            this.ficha = datos;
            this.fichaAbierta = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => window.lucide && lucide.createIcons());
          },

          cerrarFicha() {
            this.fichaAbierta = false;
            document.body.style.overflow = '';
          },

          async aplicarFiltros() {
            const form = document.getElementById('filtros-proyectos');
            const params = new URLSearchParams(new FormData(form));

            const sinCriterios = ![...params.values()].some(v => v.trim() !== '');

            if (this._peticion) this._peticion.abort();
            this._peticion = new AbortController();

            this.cargando = true;
            this.aviso = '';

            try {
              const url = '/proyectos?' + params.toString();
              const resp = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: this._peticion.signal,
              });
              if (!resp.ok) throw new Error('respuesta no válida');

              document.getElementById('resultados-proyectos').innerHTML = await resp.text();
              history.replaceState(null, '', url);
              if (window.lucide) lucide.createIcons();

              if (sinCriterios) {
                this.aviso = 'Seleccione al menos un criterio para acotar la búsqueda.';
              }
            } catch (err) {
              if (err.name === 'AbortError') return;

              this.aviso = 'El filtrado no está disponible temporalmente. Se mantienen los últimos resultados.';
            } finally {
              this.cargando = false;
            }
          },
        };
      }
    </script>
</x-app-layout>
