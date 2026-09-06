<x-app-layout>
    <div class="w-full bg-[#f5f3ec] min-h-screen pb-24">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-16">
          <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">MARCO TÉCNICO</p>
          <h1 class="text-4xl md:text-5xl font-bold text-[#1a1a1a] mb-6 tracking-tight">Certificaciones vigentes</h1>
          <p class="text-lg text-[#666666] max-w-2xl leading-relaxed">
            Normativa chilena y respaldo de organismos certificadores para cada producto y proceso.
          </p>
        </div>
      </div>

      <div class="max-w-[1200px] mx-auto px-4 lg:px-8 mt-12">
        {{-- CU 25.1 / CU 25.2: el documento solicitado no está disponible. --}}
        @if(session('doc_no_disponible'))
          <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded font-medium text-sm mb-6">
            {{ session('doc_no_disponible') }}
          </div>
        @endif

        @if($certificados->isEmpty())
          <div class="bg-white border border-[#e8e6df] rounded-lg p-16 text-center text-[#666666]">
            Aún no hay certificaciones publicadas.
          </div>
        @else
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($certificados as $cert)
              <div class="bg-white rounded-lg border border-[#e8e6df] shadow-sm p-8 flex flex-col">
                <div class="w-14 h-14 bg-[#f5f3ec] rounded flex items-center justify-center mb-6 overflow-hidden">
                  @if($cert->imagen)
                    <img src="{{ Storage::url($cert->imagen) }}" alt="{{ $cert->nombre }}" class="w-full h-full object-contain p-1.5">
                  @else
                    <i data-lucide="shield-check" class="h-7 w-7 text-[#28533c]"></i>
                  @endif
                </div>
                <h3 class="text-xl font-bold text-[#1a1a1a] mb-3">{{ $cert->nombre }}</h3>
                <p class="text-sm text-[#666666] leading-relaxed mb-6 flex-grow">{{ $cert->descripcion }}</p>

                <div class="pt-4 border-t border-[#e8e6df] space-y-2">
                  @if($cert->organismo)
                    <a href="{{ $cert->url_organismo ?: '#' }}" target="_blank" rel="noopener"
                       class="flex items-center text-sm font-semibold text-[#28533c] hover:underline">
                      <i data-lucide="external-link" class="h-4 w-4 mr-2"></i>
                      {{ $cert->organismo }}
                    </a>
                  @endif

                  {{-- RF25: si el certificado no tiene documento, la opción no se muestra.
                       CU 25.2 previsualiza en nueva pestaña; CU 25.1 descarga el archivo
                       con un nombre seguro generado por el Controlador. --}}
                  @if($cert->archivo_pdf)
                    <a href="{{ Storage::url($cert->archivo_pdf) }}" target="_blank" rel="noopener"
                       class="flex items-center text-sm font-semibold text-[#1a1a1a] hover:underline">
                      <i data-lucide="file-text" class="h-4 w-4 mr-2"></i>
                      Ver certificado (PDF)
                    </a>
                    <a href="{{ route('public.certificaciones.descargar', $cert) }}"
                       class="flex items-center text-sm font-semibold text-[#28533c] hover:underline">
                      <i data-lucide="download" class="h-4 w-4 mr-2"></i>
                      Descargar certificado
                    </a>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
</x-app-layout>
