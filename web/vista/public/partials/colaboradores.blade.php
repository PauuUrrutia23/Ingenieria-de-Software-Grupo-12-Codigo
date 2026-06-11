{{--
    Sección reutilizable de Colaboradores — Ingecon (RF14)
    Renderiza los logotipos y nombres comerciales desde la Base de Datos.

    Variable recibida:
      $colaboradores  Collection<Colaborador>  (con accessor logotipo_base64)
--}}

@if ($colaboradores->isNotEmpty())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
        @foreach ($colaboradores as $colaborador)
            <div class="flex flex-col items-center justify-center bg-white rounded-xl
                        border border-slate-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="h-16 flex items-center justify-center mb-3">
                    @if ($colaborador->logotipo_base64)
                        <img src="{{ $colaborador->logotipo_base64 }}"
                             alt="Logotipo de {{ $colaborador->nombre_comercial }}"
                             class="max-h-16 max-w-full object-contain" loading="lazy">
                    @else
                        <div class="w-16 h-16 flex items-center justify-center
                                    bg-slate-100 rounded-lg text-slate-300" aria-hidden="true">
                            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5
                                         3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <span class="text-sm font-medium text-slate-700 text-center">
                    {{ $colaborador->nombre_comercial }}
                </span>
            </div>
        @endforeach
    </div>
@else
    <p class="text-center text-slate-400 text-sm py-10">
        Aún no hay colaboradores registrados.
    </p>
@endif
