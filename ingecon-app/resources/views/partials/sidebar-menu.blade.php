{{--
    Menú Lateral Deslizante — Ingecon (RF12 / CU 2.2)
    Se abre disparando el evento Alpine global 'abrir-sidebar'.
    Se cierra: botón X, click en overlay, o al seleccionar una sección.
--}}

<div
    id="sidebar-panel"
    x-data="{ abierto: false }"
    @abrir-sidebar.window="abierto = true"
    @keydown.escape.window="abierto = false"
    role="dialog"
    aria-modal="true"
    aria-label="Menú de navegación lateral"
    x-bind:aria-hidden="!abierto"
>

    {{-- ================================================================
         Overlay oscuro detrás del panel
         ================================================================ --}}
    <div
        x-show="abierto"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="abierto = false"
        class="fixed inset-0 bg-black/50 z-40 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- ================================================================
         Panel lateral deslizante
         ================================================================ --}}
    <aside
        x-show="abierto"
        x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed left-0 top-0 h-full w-64 bg-white z-50 shadow-xl
               flex flex-col overflow-y-auto"
    >

        {{-- ============================================================
             Cabecera del panel — logo + botón cerrar
             ============================================================ --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 bg-slate-900 rounded-md flex items-center justify-center"
                     aria-hidden="true">
                    <span class="text-white text-xs font-black tracking-tighter">IC</span>
                </div>
                <span class="text-slate-900 font-bold text-base tracking-wide">
                    INGECON
                </span>
            </div>

            {{-- Botón cerrar (X) --}}
            <button
                type="button"
                @click="abierto = false"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700
                       hover:bg-slate-100 transition-colors
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                aria-label="Cerrar menú"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- ============================================================
             Etiqueta de sección de navegación
             ============================================================ --}}
        <p class="px-5 pt-5 pb-2 text-xs font-semibold text-slate-400 uppercase tracking-widest">
            Secciones
        </p>

        {{-- ============================================================
             Links de navegación (RF12 — CU 2.2)
             Cada link: cierra el panel + scroll suave a la sección
             ============================================================ --}}
        <nav aria-label="Menú lateral de secciones">
            <ul class="flex flex-col px-3 gap-0.5" role="list">

                {{-- Inicio --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('inicio').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono casa --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 12l8.954-8.955c.44-.439 1.152-.439
                                     1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504
                                     1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125
                                     1.125-1.125h2.25c.621 0 1.125.504 1.125
                                     1.125V21h4.125c.621 0 1.125-.504
                                     1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                        Inicio
                    </button>
                </li>

                {{-- Proyectos --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('proyectos').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono edificio/construcción --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9
                                     6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5
                                     3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125
                                     1.125-1.125h3.75c.621 0 1.125.504
                                     1.125 1.125V21"/>
                        </svg>
                        Proyectos
                    </button>
                </li>

                {{-- Certificaciones --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('certificaciones').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono medalla/certificado --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63
                                     2.39-1.593 3.068a3.745 3.745 0 01-1.043
                                     3.296 3.745 3.745 0 01-3.296 1.043A3.745
                                     3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746
                                     3.746 0 01-3.296-1.043 3.745 3.745 0
                                     01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39
                                     1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746
                                     0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63
                                     3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746
                                     0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                        </svg>
                        Certificaciones
                    </button>
                </li>

                {{-- Contacto --}}
                <li>
                    <button
                        type="button"
                        @click="
                            abierto = false;
                            $nextTick(() => {
                                document.getElementById('contacto').scrollIntoView({ behavior: 'smooth' });
                            })
                        "
                        class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                               text-sm font-medium text-slate-700
                               hover:bg-slate-100 hover:text-slate-900
                               transition-colors text-left"
                    >
                        {{-- Ícono sobre/mensaje --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25
                                     2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5
                                     0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0
                                     00-2.25 2.25m19.5 0v.243a2.25 2.25 0
                                     01-1.07 1.916l-7.5 4.615a2.25 2.25 0
                                     01-2.36 0L3.32 8.91a2.25 2.25 0
                                     01-1.07-1.916V6.75"/>
                        </svg>
                        Contacto
                    </button>
                </li>

            </ul>
        </nav>

        {{-- ============================================================
             Separador y acceso rápido al panel de administración
             ============================================================ --}}
        <div class="mt-auto px-3 pb-5 pt-4 border-t border-slate-100">
            <button
                type="button"
                @click="
                    abierto = false;
                    $nextTick(() => $dispatch('abrir-login'));
                "
                class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg
                       text-sm font-medium text-slate-500
                       hover:bg-slate-100 hover:text-slate-900
                       transition-colors text-left"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-400 shrink-0"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor"
                     stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75
                             11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25
                             2.25 0 00-2.25-2.25H6.75a2.25 2.25 0
                             00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                Acceso al panel
            </button>
        </div>

    </aside>

</div>
