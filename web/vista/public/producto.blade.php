<x-app-layout>
    <div class="w-full bg-white">
        <div class="border-b border-gray-200">
            <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-5">
                <div class="text-[13px] font-medium text-[#666666] flex items-center space-x-2">
                    <a href="{{ route('public.producto') }}" class="hover:text-[#1a1a1a]">Productos</a>
                    <span>/</span>
                    <span class="text-[#1a1a1a] font-bold">Conectores metálicos</span>
                </div>
            </div>
        </div>

        @if(session('doc_no_disponible'))
            <div class="max-w-[1200px] mx-auto px-4 lg:px-8 pt-6">
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded font-medium text-sm">
                    {{ session('doc_no_disponible') }}
                </div>
            </div>
        @endif

        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-14 lg:py-20">
            <div class="flex flex-col lg:flex-row gap-16">

                <div class="w-full lg:w-1/2">
                    <div class="flex items-center justify-center text-[#99968f] text-xs font-medium tracking-widest uppercase bg-[#e8e6df] w-full aspect-[4/3] rounded-lg mb-4"
                         style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)">
                        FOTOGRAFÍA DEL CONECTOR METÁLICO
                    </div>
                    <div class="grid grid-cols-4 gap-4">
                        @for($i = 0; $i < 4; $i++)
                            <div class="aspect-square rounded-md border-2 border-[#e8e6df] bg-[#e8e6df]" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(0,0,0,0.04) 2px, rgba(0,0,0,0.04) 4px)"></div>
                        @endfor
                    </div>
                </div>

                <div class="w-full lg:w-1/2 pt-2">
                    <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-4">PRODUCTO</p>
                    <h1 class="text-3xl md:text-[40px] font-bold text-[#1a1a1a] mb-6 tracking-tight">Conectores metálicos</h1>

                    <p class="text-[#666666] mb-10 leading-relaxed text-[17px]">
                        Placas y conectores de acero para el ensamble de cerchas, entramados y estructuras de madera.
                        Fabricados con acero de proveedores certificados y dimensionados según el cálculo de cada proyecto.
                    </p>

                    <h3 class="text-lg font-bold text-[#1a1a1a] mb-5">Especificaciones técnicas</h3>

                    <div class="border border-[#e8e6df] rounded-lg overflow-hidden mb-10">
                        <table class="w-full text-sm text-left">
                            <tbody>
                                <tr class="border-b border-[#e8e6df]">
                                    <td class="px-5 py-4 text-[#666666] bg-white w-1/3">Material</td>
                                    <td class="px-5 py-4 font-bold text-[#1a1a1a] bg-white">Acero estructural galvanizado</td>
                                </tr>
                                <tr class="border-b border-[#e8e6df]">
                                    <td class="px-5 py-4 text-[#666666] bg-white">Espesor</td>
                                    <td class="px-5 py-4 font-bold text-[#1a1a1a] bg-white">1.5 - 3.0 mm</td>
                                </tr>
                                <tr class="border-b border-[#e8e6df]">
                                    <td class="px-5 py-4 text-[#666666] bg-white">Tratamiento superficial</td>
                                    <td class="px-5 py-4 font-bold text-[#1a1a1a] bg-white">Galvanizado en caliente</td>
                                </tr>
                                <tr class="border-b border-[#e8e6df]">
                                    <td class="px-5 py-4 text-[#666666] bg-white">Formatos disponibles</td>
                                    <td class="px-5 py-4 font-bold text-[#1a1a1a] bg-white">Según cálculo estructural del proyecto</td>
                                </tr>
                                <tr>
                                    <td class="px-5 py-4 text-[#666666] bg-white">Normativa aplicable</td>
                                    <td class="px-5 py-4 font-bold text-[#1a1a1a] bg-white">ANSI/TPI, NCh 1198</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/#contacto" class="flex-1 bg-[#28533c] text-white px-6 py-3.5 rounded font-semibold hover:bg-[#1e402e] transition-colors text-center text-sm">
                            Consultar por este producto
                        </a>
                        <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="flex-1 border border-gray-300 text-[#1a1a1a] px-6 py-3.5 rounded font-semibold hover:bg-gray-50 transition-colors flex items-center justify-center text-sm">
                            <i data-lucide="download" class="w-4 h-4 mr-2" stroke-width="2.5"></i> Ficha técnica
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-[#f5f3ec] py-24">
            <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
                <h2 class="text-3xl font-bold text-[#1a1a1a] mb-12 tracking-tight">Dónde se usan</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-8 rounded-xl border border-[#e8e6df]">
                        <div class="w-12 h-12 bg-[#eaf0ec] rounded-lg flex items-center justify-center mb-6">
                            <i data-lucide="home" class="w-6 h-6 text-[#28533c]"></i>
                        </div>
                        <h3 class="text-xl font-bold text-[#1a1a1a] mb-3">Cerchas de techumbre</h3>
                        <p class="text-[#666666] text-[15px] leading-relaxed">Unión de elementos en cerchas fabricadas en serie.</p>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-[#e8e6df]">
                        <div class="w-12 h-12 bg-[#eaf0ec] rounded-lg flex items-center justify-center mb-6">
                            <i data-lucide="building-2" class="w-6 h-6 text-[#28533c]"></i>
                        </div>
                        <h3 class="text-xl font-bold text-[#1a1a1a] mb-3">Entramados de muro</h3>
                        <p class="text-[#666666] text-[15px] leading-relaxed">Fijación de paneles en vivienda industrializada.</p>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-[#e8e6df]">
                        <div class="w-12 h-12 bg-[#eaf0ec] rounded-lg flex items-center justify-center mb-6">
                            <i data-lucide="factory" class="w-6 h-6 text-[#28533c]"></i>
                        </div>
                        <h3 class="text-xl font-bold text-[#1a1a1a] mb-3">Estructuras de gran luz</h3>
                        <p class="text-[#666666] text-[15px] leading-relaxed">Nudos de pabellones y galpones industriales.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-24">
            <div class="bg-[#3a614b] rounded-xl p-10 md:p-16 flex flex-col md:flex-row items-center justify-between text-white relative overflow-hidden">
                <div class="relative z-10 mb-8 md:mb-0 md:mr-8 w-full md:w-2/3">
                    <h2 class="text-3xl font-bold mb-4 tracking-tight">Documentación técnica</h2>
                    <p class="text-[#d8e3dc] max-w-xl text-lg leading-relaxed">
                        Planillas de cálculo, tablas de carga y detalles de montaje para especificadores y calculistas.
                    </p>
                </div>
                <div class="relative z-10 w-full md:w-auto">
                    <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="block text-center w-full md:w-auto bg-white text-[#1a1a1a] px-8 py-4 rounded font-semibold hover:bg-gray-100 transition-colors text-sm">
                        Ver documentación
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
