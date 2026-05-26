{{--
    Sección Certificaciones Pública — Ingecon (RF25, RF26)
    CU 4.1: Listado de certificados activos con metadatos
    CU 4.2: Descarga directa de PDF vía enlace

    Variables recibidas:
      $certificados  \Illuminate\Database\Eloquent\Collection<Certificado>
                     Con relación proyecto cargada (eager loaded).
                     Incluye propiedad fecha_formateada (string d/m/Y).
--}}

{{-- ====================================================================
     CABECERA DE SECCIÓN
     ==================================================================== --}}
<div class="text-center mb-10">
    <h2
        id="certificaciones-titulo"
        class="text-3xl font-bold text-slate-900 mb-3"
    >
        Certificaciones
    </h2>
    <p class="text-slate-600 text-lg max-w-xl mx-auto">
        Documentación de calidad y cumplimiento normativo de nuestras obras ejecutadas.
    </p>
</div>

{{-- ====================================================================
     ESTADO VACÍO — Sin certificados activos
     ==================================================================== --}}
@if($certificados->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center">
        <svg
            class="w-14 h-14 text-slate-300 mb-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.25"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125
                     1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0
                     12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125
                     1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0
                     1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <p class="text-slate-500 font-medium text-base mb-1">
            No hay certificaciones disponibles por el momento
        </p>
        <p class="text-slate-400 text-sm max-w-xs">
            Los certificados técnicos de nuestros proyectos aparecerán aquí cuando estén disponibles.
        </p>
    </div>

@else

{{-- ====================================================================
     GRILLA DE TARJETAS DE CERTIFICADOS
     ==================================================================== --}}
<div
    class="grid grid-cols-1 md:grid-cols-2 gap-4"
    role="list"
    aria-label="Listado de certificados disponibles para descarga"
>

    @foreach($certificados as $cert)
        <article
            class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm
                   hover:shadow-md transition-shadow duration-200
                   flex items-center justify-between gap-4"
            role="listitem"
        >

            {{-- ============================================================
                 LADO IZQUIERDO — Metadatos del certificado
                 ============================================================ --}}
            <div class="flex items-start gap-4 min-w-0">

                {{-- Ícono PDF --}}
                <div class="shrink-0 w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center"
                     aria-hidden="true">
                    <svg
                        class="w-6 h-6 text-red-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.75"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125
                                 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25
                                 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125
                                 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0
                                 00-9-9z"/>
                    </svg>
                </div>

                {{-- Datos textuales --}}
                <div class="min-w-0">

                    {{-- Código de lote --}}
                    <p class="font-bold text-slate-900 text-sm truncate"
                       title="{{ $cert->codigo_lote }}">
                        {{ $cert->codigo_lote }}
                    </p>

                    {{-- Nombre del proyecto (relación eager loaded) --}}
                    @if($cert->proyecto)
                        <p class="text-slate-500 text-xs mt-0.5 truncate"
                           title="{{ $cert->proyecto->nombre_obra }}">
                            {{ $cert->proyecto->nombre_obra }}
                        </p>
                        @if($cert->proyecto->region)
                            <p class="text-slate-400 text-xs truncate">
                                {{ $cert->proyecto->region }}
                            </p>
                        @endif
                    @endif

                    {{-- Fecha de emisión formateada --}}
                    <div class="flex items-center gap-1 mt-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0"
                             xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0
                                     012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18
                                     0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021
                                     18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25
                                     2.25 0 0121 11.25v7.5"/>
                        </svg>
                        <time
                            datetime="{{ $cert->fecha_emision?->format('Y-m-d') }}"
                            class="text-xs text-slate-400"
                        >
                            {{ $cert->fecha_formateada }}
                        </time>
                    </div>

                </div>
            </div>

            {{-- ============================================================
                 LADO DERECHO — Botón de descarga (RF26 — CU 4.2)
                 ============================================================ --}}
            <div class="shrink-0">
                <a
                    href="{{ route('certificaciones.descargar', $cert->id_certificado) }}"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700
                           active:bg-blue-800 text-white text-sm font-semibold
                           px-4 py-2 rounded-lg transition-colors duration-150
                           focus:outline-none focus-visible:ring-2
                           focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                    download
                    aria-label="Descargar certificado {{ $cert->codigo_lote }} en PDF"
                >
                    {{-- Ícono descarga --}}
                    <svg
                        class="w-4 h-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0
                                 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5
                                 4.5V3"/>
                    </svg>
                    Descargar
                </a>
            </div>

        </article>
    @endforeach

</div>

@endif
