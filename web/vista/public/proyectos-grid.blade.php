@php
    // El nodo contenedor de este fragmento se reemplaza por completo con
    // innerHTML al filtrar, así que aquí no se usan clases de revelado por
    // scroll: el observer de resources/js/app.js sólo corre al cargar la
    // página y dejaría las obras invisibles tras un filtro.
    $colecciones = $proyectos->getCollection()->chunk(3);
@endphp

{{-- ============ CONTADOR / METADATOS (mono) ============ --}}
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-line">
  @if($proyectos->total() > 0)
    <p class="ig-meta mb-0">
      {{ $proyectos->total() }} {{ $proyectos->total() === 1 ? 'obra' : 'obras' }}
      <span class="text-line-strong">/</span>
      mostrando {{ $proyectos->firstItem() }}–{{ $proyectos->lastItem() }} en esta página
    </p>
  @else
    <p class="ig-meta mb-0">{{ ($galeriaNoDisponible ?? false) ? 'El listado no está disponible temporalmente.' : 'Sin resultados' }}</p>
  @endif

  @if(request('q') || request('categoria') || request('region'))
    <a href="/proyectos" class="ig-link-ghost shrink-0">
      <i data-lucide="x" class="h-3.5 w-3.5"></i> Limpiar filtros
    </a>
  @endif
</div>

{{-- ============ GALERÍA EDITORIAL ============
     Bandas de tres obras: una destacada (7/12) y dos secundarias (5/12)
     apiladas, con proporciones distintas. La última banda puede traer
     una o dos obras y se recompone.
--}}
@forelse($colecciones as $bloque)
  @php
    $total_bloque = $bloque->count();
    $columnas_destacado = $total_bloque === 1 ? 'lg:col-span-12' : 'lg:col-span-7';
    $alto_secundaria = $total_bloque === 2 ? 'h-[280px] md:h-[320px] lg:h-[420px]' : 'h-[280px] sm:h-[300px] lg:h-[202px]';
  @endphp

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">
    @foreach($bloque->take(1) as $proyecto)
      <button type="button"
              class="js-abrir-ficha group ig-shot {{ $columnas_destacado }} h-[280px] md:h-[320px] lg:h-[420px] w-full text-left focus-visible:outline-offset-[-4px]"
              data-proyecto-id="{{ $proyecto->id_proyecto }}">
        @if($proyecto->imagenes->isNotEmpty())
          <img src="{{ Storage::url($proyecto->imagenes->first()->imagen) }}"
               alt="{{ $proyecto->nombre_obra }}" loading="lazy"
               onerror="this.style.display='none'">
        @else
          <span class="ig-plate"><span class="ig-plate-label">Sin fotografía</span></span>
        @endif

        <span class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-carbon/95 via-carbon/55 to-transparent"></span>
        <span class="ig-shot-veil"></span>

        <span class="absolute inset-x-0 bottom-0 p-6 md:p-8 flex flex-col items-start">
          <span class="font-mono text-[0.6875rem] uppercase tracking-[0.16em] text-wood-light mb-3">{{ \App\Support\CategoriasProyecto::etiqueta($proyecto->categoria) }}</span>
          <span class="font-display text-2xl md:text-3xl lg:text-4xl font-light tracking-tight text-white mb-3">{{ $proyecto->nombre_obra }}</span>
          <span class="font-mono text-[0.6875rem] uppercase tracking-[0.12em] text-white/65">
            {{ $proyecto->ubicacion_geografica }} <span class="text-white/35">·</span> {{ $proyecto->anio_ejecucion }}
          </span>
          <span class="mt-5 inline-flex items-center gap-2.5 font-mono text-[0.625rem] uppercase tracking-[0.16em] text-white/85
                       opacity-0 -translate-y-1 transition-all duration-500
                       lg:group-hover:opacity-100 lg:group-hover:translate-y-0">
            <span class="h-px w-6 bg-wood transition-all duration-500 lg:group-hover:w-10"></span>
            Ver especificaciones técnicas
          </span>
        </span>
      </button>
    @endforeach

    @if($total_bloque > 1)
      <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
        @foreach($bloque->slice(1) as $proyecto)
          <button type="button"
                  class="js-abrir-ficha group ig-shot {{ $alto_secundaria }} w-full text-left focus-visible:outline-offset-[-4px]"
                  data-proyecto-id="{{ $proyecto->id_proyecto }}">
            @if($proyecto->imagenes->isNotEmpty())
              <img src="{{ Storage::url($proyecto->imagenes->first()->imagen) }}"
                   alt="{{ $proyecto->nombre_obra }}" loading="lazy"
                   onerror="this.style.display='none'">
            @else
              <span class="ig-plate"><span class="ig-plate-label">Sin fotografía</span></span>
            @endif

            <span class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-carbon/95 via-carbon/55 to-transparent"></span>
            <span class="ig-shot-veil"></span>

            <span class="absolute inset-x-0 bottom-0 p-6 flex flex-col items-start">
              <span class="font-mono text-[0.625rem] uppercase tracking-[0.16em] text-wood-light mb-2.5">{{ \App\Support\CategoriasProyecto::etiqueta($proyecto->categoria) }}</span>
              <span class="font-display text-xl lg:text-2xl font-light tracking-tight text-white mb-2.5">{{ $proyecto->nombre_obra }}</span>
              <span class="font-mono text-[0.625rem] uppercase tracking-[0.12em] text-white/65">
                {{ $proyecto->ubicacion_geografica }} <span class="text-white/35">·</span> {{ $proyecto->anio_ejecucion }}
              </span>
              <span class="mt-4 inline-flex items-center gap-2.5 font-mono text-[0.625rem] uppercase tracking-[0.16em] text-white/85
                           opacity-0 -translate-y-1 transition-all duration-500
                           lg:group-hover:opacity-100 lg:group-hover:translate-y-0">
                <span class="h-px w-6 bg-wood transition-all duration-500 lg:group-hover:w-10"></span>
                Ver especificaciones técnicas
              </span>
            </span>
          </button>
        @endforeach
      </div>
    @endif
  </div>
@empty
  {{-- ============ SIN RESULTADOS (estado diseñado) ============ --}}
  <div class="border border-line bg-surface">
    <div class="grid grid-cols-1 lg:grid-cols-12">
      <div class="lg:col-span-4 relative min-h-[200px]">
        <span class="ig-plate"><span class="ig-plate-label">Banco sin coincidencias</span></span>
      </div>
      <div class="lg:col-span-8 p-8 md:p-12">
        <p class="font-mono text-[0.6875rem] uppercase tracking-[0.22em] text-wood-deep mb-5">00 resultados</p>
        <h3 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-4">Ninguna obra coincide</h3>
        <p class="ig-lede mb-9">No se encontraron proyectos que cumplan todos los criterios.</p>
        <div class="flex flex-wrap items-center gap-x-8 gap-y-4">
          <a href="/proyectos" class="ig-btn ig-btn-primary">Ver todas las obras</a>
          <a href="/proyectos" class="ig-link-ghost"><i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i> Limpiar filtros</a>
        </div>
      </div>
    </div>
  </div>
@endforelse

{{-- ============ PAGINACIÓN ============ --}}
@if($proyectos->hasPages())
  <nav class="mt-14 border-t border-line pt-8 flex flex-col sm:flex-row items-center justify-between gap-5"
       aria-label="Paginación de obras">
    <p class="ig-meta mb-0">Página {{ $proyectos->currentPage() }} de {{ $proyectos->lastPage() }}</p>
    <div class="flex flex-wrap items-center justify-center gap-2
                [&_svg]:h-4 [&_svg]:w-4
                [&_a]:rounded-none [&_a]:border-line-strong [&_a]:text-mute-deep
                [&_[aria-current]]:rounded-none [&_[aria-current]]:border-carbon
                [&_[aria-current]]:bg-carbon [&_[aria-current]]:text-white">
      {{ $proyectos->withQueryString()->links() }}
    </div>
  </nav>
@else
  <p class="mt-14 border-t border-line pt-8 ig-meta mb-0">Fin del registro de obras</p>
@endif
