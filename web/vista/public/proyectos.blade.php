<x-app-layout titulo="Proyectos ejecutados — Ingecon"
              descripcion="Obras entregadas a constructoras, inmobiliarias y clientes industriales: viviendas industrializadas, galpones y terminaciones de madera.">
    <div class="w-full bg-paper min-h-screen"
         x-data="galeriaProyectos()"
         @keydown.escape.window="cerrarFicha()">

      @php $hayFiltros = (bool) (request('q') || request('categoria') || request('region')); @endphp

      {{-- ========================= MASTHEAD =========================
           Sin hero: la página nace bajo el navbar fijo (pt-32) y el nav
           arranca sólido porque esta vista no declara overlay. --}}
      <section class="bg-carbon text-white ig-blueprint pt-32 pb-16 md:pt-36 md:pb-20 border-b border-white/10">
        <div class="ig-container">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-end">
            <div class="lg:col-span-8 ig-reveal">
              <p class="ig-eyebrow ig-eyebrow-on-dark mb-7">Obras ejecutadas</p>
              <h1 class="ig-h-display text-white mb-7">Nuestros proyectos</h1>
              <p class="ig-lede max-w-2xl text-white/60">
                Obras entregadas a constructoras, inmobiliarias y clientes industriales
                a lo largo del país.
              </p>
            </div>
            <div class="lg:col-span-4 ig-reveal ig-d2">
              <dl class="border-t border-white/12">
                <div class="flex items-baseline justify-between gap-6 py-4 border-b border-white/12">
                  <dt class="font-mono text-[0.6875rem] uppercase tracking-[0.14em] text-white/45">Galería</dt>
                  <dd class="font-mono text-[0.6875rem] uppercase tracking-[0.14em] text-wood">Completa</dd>
                </div>
                <div class="flex items-baseline justify-between gap-6 py-4 border-b border-white/12">
                  <dt class="font-mono text-[0.6875rem] uppercase tracking-[0.14em] text-white/45">Líneas</dt>
                  <dd class="font-mono text-[0.6875rem] uppercase tracking-[0.14em] text-white/70">Construcción · Industrial · Terminaciones</dd>
                </div>
                <div class="flex items-baseline justify-between gap-6 py-4 border-b border-white/12">
                  <dt class="font-mono text-[0.6875rem] uppercase tracking-[0.14em] text-white/45">Cobertura</dt>
                  <dd class="font-mono text-[0.6875rem] uppercase tracking-[0.14em] text-white/70">Nacional</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </section>

      {{-- ========================= FILTROS ========================= --}}
      <section class="bg-surface border-b border-line ig-reveal ig-d2">
        <div class="ig-container py-10 md:py-12">
          <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-9">
            <p class="ig-eyebrow mb-0">Acotar la búsqueda</p>
            @if($hayFiltros)
              <span class="ig-link-ghost"><i data-lucide="filter" class="h-3.5 w-3.5 text-wood-deep"></i> Filtro activo</span>
            @else
              <span class="ig-meta">Sin filtros aplicados</span>
            @endif
          </div>

          <form id="filtros-proyectos" action="/proyectos" method="GET"
                @submit.prevent="aplicarFiltros()"
                class="grid grid-cols-1 md:grid-cols-12 gap-x-8 gap-y-9 items-end">
            <div class="md:col-span-5">
              <label for="filtro-q" class="ig-label">Buscar por nombre o ubicación</label>
              <div class="relative">
                <i data-lucide="search" class="h-4 w-4 absolute left-0 top-1/2 -translate-y-1/2 pointer-events-none text-mute"></i>
                <input
                  id="filtro-q"
                  type="text"
                  name="q"
                  value="{{ request('q') }}"
                  placeholder="Ej. galpón, Los Ángeles"
                  class="ig-input pl-7"
                />
              </div>
            </div>
            <div class="md:col-span-3">
              <label for="filtro-categoria" class="ig-label">Línea de producto</label>
              <div class="relative">
                <select id="filtro-categoria" name="categoria" class="ig-select appearance-none pr-7">
                  <option value="">Todas</option>
                  <option value="construccion" {{ request('categoria') == 'construccion' ? 'selected' : '' }}>Construcción</option>
                  <option value="industrial" {{ request('categoria') == 'industrial' ? 'selected' : '' }}>Industrial</option>
                  <option value="terminaciones" {{ request('categoria') == 'terminaciones' ? 'selected' : '' }}>Terminaciones</option>
                </select>
                <i data-lucide="chevron-down" class="h-3.5 w-3.5 absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none text-mute"></i>
              </div>
            </div>
            <div class="md:col-span-2">
              <label for="filtro-region" class="ig-label">Región</label>
              <div class="relative">
                <select id="filtro-region" name="region" class="ig-select appearance-none pr-7">
                  <option value="">Todas</option>
                  @if(isset($regiones))
                    @foreach($regiones as $r)
                      <option value="{{ $r }}" {{ request('region') == $r ? 'selected' : '' }}>{{ $r }}</option>
                    @endforeach
                  @endif
                </select>
                <i data-lucide="chevron-down" class="h-3.5 w-3.5 absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none text-mute"></i>
              </div>
            </div>
            <div class="md:col-span-2">
              <button type="submit" class="ig-btn ig-btn-primary w-full"
                      :disabled="cargando">
                <span x-show="!cargando">Aplicar Filtros</span>
                <span x-show="cargando" style="display:none;">Buscando…</span>
              </button>
            </div>
          </form>

          <p x-show="aviso" style="display:none;" x-text="aviso"
             class="mt-8 mb-0 text-xs font-normal leading-relaxed tracking-wide text-[#8c2f2f]"></p>
        </div>
      </section>

      {{-- ====================== RESULTADOS ======================
           Este nodo se reemplaza por completo con innerHTML al filtrar, así
           que su contenido (el partial) no usa revelados por scroll: el
           observer de resources/js/app.js sólo corre al cargar. --}}
      <section class="bg-paper ig-section-tight">
        <div class="ig-container">
          <div id="resultados-proyectos" :class="cargando ? 'opacity-50 transition-opacity' : ''">
            @include('public.partials.proyectos-grid')
          </div>
        </div>
      </section>

      {{-- ======================== FICHA ======================== --}}
      <div x-show="fichaAbierta" x-cloak style="display:none;"
           class="fixed inset-0 z-[80] flex items-center justify-center p-4 md:p-8">
        <div class="absolute inset-0 bg-carbon/85 backdrop-blur-sm" @click="cerrarFicha()"></div>

        <div class="relative bg-surface border border-line w-full max-w-4xl max-h-full overflow-y-auto"
             role="dialog" aria-modal="true" aria-labelledby="ficha-titulo">

          <button type="button" @click="cerrarFicha()" aria-label="Cerrar ficha técnica"
                  class="absolute top-0 right-0 z-10 h-11 w-11 bg-surface border-b border-l border-line flex items-center justify-center text-mute hover:text-carbon hover:border-wood transition-colors">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>

          <div class="grid grid-cols-1 lg:grid-cols-12">
            <div class="lg:col-span-5">
              <template x-if="ficha.imagenes && ficha.imagenes.length">
                <img :src="ficha.imagenes[0]" :alt="ficha.nombre"
                     class="w-full h-64 md:h-80 lg:h-full min-h-full object-cover"
                     onerror="this.style.display='none'">
              </template>
              <template x-if="!ficha.imagenes || !ficha.imagenes.length">
                <div class="relative w-full h-64 md:h-80 lg:h-full min-h-full">
                  <span class="ig-plate"><span class="ig-plate-label">Sin fotografía</span></span>
                </div>
              </template>
            </div>

            <div class="lg:col-span-7 p-8 md:p-12">
              <p class="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-wood-deep mb-5"
                 x-text="ficha.categoria"></p>

              <h2 id="ficha-titulo" class="font-display text-3xl md:text-4xl font-light tracking-tight mb-6"
                  x-text="ficha.nombre"></h2>

              <dl class="grid grid-cols-2 gap-x-6 gap-y-5 mb-9 pb-9 border-b border-line">
                <div>
                  <dt class="ig-meta mb-1.5">Ubicación</dt>
                  <dd class="text-sm font-light text-mute-deep" x-text="ficha.ubicacion"></dd>
                </div>
                <div>
                  <dt class="ig-meta mb-1.5">Año de ejecución</dt>
                  <dd class="text-sm font-light text-mute-deep" x-text="ficha.anio"></dd>
                </div>
              </dl>

              <h3 class="ig-meta mb-4 text-carbon">Descripción técnica</h3>
              <p class="text-mute-deep font-light leading-relaxed whitespace-pre-wrap mb-10"
                 x-text="ficha.descripcion"></p>

              <template x-if="ficha.imagenes && ficha.imagenes.length > 1">
                <div>
                  <h3 class="ig-meta mb-4">Registro fotográfico</h3>
                  <div class="grid grid-cols-3 gap-px bg-line">
                    <template x-for="(img, i) in ficha.imagenes.slice(1)" :key="i">
                      <img :src="img" :alt="ficha.nombre" class="w-full h-24 object-cover bg-surface"
                           onerror="this.style.display='none'">
                    </template>
                  </div>
                </div>
              </template>
            </div>
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
              if (window.igRefrescarContenidoDinamico) window.igRefrescarContenidoDinamico(document.getElementById('resultados-proyectos'));

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
