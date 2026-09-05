<x-app-layout>
    <div class="w-full bg-[#f5f3ec] min-h-screen pb-24">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-16">
          <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">ALIANZAS</p>
          <h1 class="text-4xl md:text-5xl font-bold text-[#1a1a1a] mb-6 tracking-tight">Colaboradores</h1>
          <p class="text-lg text-[#666666] max-w-2xl leading-relaxed">
            Proveedores y socios estratégicos que respaldan nuestros proyectos.
          </p>
        </div>
      </div>

      <div class="max-w-[1200px] mx-auto px-4 lg:px-8 mt-12">
        @if($colaboradores->isEmpty())
          <div class="bg-white border border-[#e8e6df] rounded-lg p-16 text-center text-[#666666]">
            Aún no hay colaboradores.
          </div>
        @else
          <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($colaboradores as $prov)
              <div class="bg-white rounded-lg border border-[#e8e6df] shadow-sm p-6 flex flex-col items-center justify-center text-center">
                <div class="flex items-center justify-center w-full h-[80px] mb-3">
                  <img src="{{ Storage::url($prov->logotipo) }}" alt="{{ $prov->nombre_comercial }}" class="max-h-full max-w-full object-contain">
                </div>
                <span class="text-sm font-semibold text-[#1a1a1a]">{{ $prov->nombre_comercial }}</span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
</x-app-layout>
