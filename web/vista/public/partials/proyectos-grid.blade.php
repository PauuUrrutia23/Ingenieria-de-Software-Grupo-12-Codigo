<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
  <span class="font-bold text-[#1a1a1a]">{{ $proyectos->total() }} {{ $proyectos->total() === 1 ? 'obra' : 'obras' }}</span>
  @if(request('q') || request('categoria') || request('region'))
    <a href="/proyectos" class="text-sm font-medium text-gray-500 hover:text-[#1a1a1a]">Limpiar filtros</a>
  @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
  @forelse($proyectos as $proyecto)
    <button type="button"
            class="js-abrir-ficha group text-left bg-white border border-[#e8e6df] rounded-lg overflow-hidden flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-[#28533c] focus:outline-none focus:ring-2 focus:ring-[#28533c]"
            data-proyecto="{{ json_encode([
                'nombre'      => $proyecto->nombre_obra,
                'descripcion' => $proyecto->descripcion_tecnica,
                'ubicacion'   => $proyecto->ubicacion_geografica,
                'categoria'   => $proyecto->categoria,
                'anio'        => $proyecto->anio_ejecucion,
                'imagenes'    => $proyecto->imagenes->map(fn ($i) => Storage::url($i->imagen))->values(),
            ], JSON_UNESCAPED_UNICODE) }}">
      <div class="relative overflow-hidden">
        @if($proyecto->imagenes->isNotEmpty())
          <img src="{{ Storage::url($proyecto->imagenes->first()->imagen) }}"
               alt="{{ $proyecto->nombre_obra }}"
               class="w-full h-[240px] object-cover transition-transform duration-500 group-hover:scale-105"
               onerror="this.style.display='none'">
        @else
          <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full h-[240px]" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">SIN IMAGEN</div>
        @endif
        <div class="absolute inset-0 bg-white/0 transition-colors duration-300 group-hover:bg-white/25"></div>
        <span class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100">
          <span class="h-12 w-12 rounded-full bg-white/90 text-[#28533c] shadow-lg flex items-center justify-center">
            <i data-lucide="plus" class="w-6 h-6"></i>
          </span>
        </span>
      </div>

      <div class="p-6 flex-grow flex flex-col">
        <span class="inline-block bg-[#eaf0ec] text-[#28533c] text-[11px] font-bold px-3 py-1 rounded-full w-max mb-5 uppercase tracking-wide">
          {{ $proyecto->categoria }}
        </span>
        <h3 class="text-xl font-bold text-[#1a1a1a] mb-8 uppercase">{{ $proyecto->nombre_obra }}</h3>
        <div class="mt-auto flex justify-between items-center text-sm text-[#666666]">
          <span class="flex items-center">
            <i data-lucide="map-pin" class="w-4 h-4 mr-1.5 opacity-60"></i> {{ $proyecto->ubicacion_geografica }}
          </span>
          <span class="font-medium">{{ $proyecto->anio_ejecucion }}</span>
        </div>
        <span class="mt-4 text-sm font-bold text-[#28533c] flex items-center">
          Ver especificaciones técnicas <i data-lucide="arrow-right" class="ml-1 w-4 h-4"></i>
        </span>
      </div>
    </button>
  @empty
    <div class="col-span-full text-center py-16 bg-white border border-[#e8e6df] rounded-lg">
      <p class="text-[#666666] mb-4">No se encontraron proyectos que cumplan todos los criterios.</p>
      <a href="/proyectos" class="text-sm font-bold text-[#28533c] hover:underline">Ver todos los proyectos</a>
    </div>
  @endforelse
</div>

<div class="flex justify-center space-x-2">
  {{ $proyectos->withQueryString()->links() }}
</div>
