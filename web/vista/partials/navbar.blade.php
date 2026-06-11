{{--
    Barra de Navegación Fija — Ingecon (RF13 / CU 2.3)
    Permanece visible durante el scroll vertical.
    Comunicación con sidebar-menu vía evento Alpine: 'abrir-sidebar'
    Comunicación con modal de login vía evento Alpine: 'abrir-login'
--}}

<header
    class="fixed top-0 left-0 right-0 z-50 bg-white shadow-md h-16"
    x-data
>
    <div class="h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">

        {{-- ============================================================
             IZQUIERDA — Ícono hamburger (abre el menú lateral RF12)
             ============================================================ --}}
        <button
            type="button"
            @click="$dispatch('abrir-sidebar')"
            class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100
                   transition-colors focus:outline-none focus-visible:ring-2
                   focus-visible:ring-slate-400"
            aria-label="Abrir menú de navegación"
            aria-expanded="false"
            aria-controls="sidebar-panel"
        >
            {{-- Ícono hamburger (3 líneas) --}}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>

        {{-- ============================================================
             CENTRO — Logotipo / Nombre de la empresa
             ============================================================ --}}
        <div class="absolute left-1/2 -translate-x-1/2 flex items-center gap-2">
            {{-- Placeholder logotipo — reemplazar con <img> cuando exista el asset --}}
            <div class="w-8 h-8 bg-slate-900 rounded-md flex items-center justify-center shrink-0"
                 aria-hidden="true">
                <span class="text-white text-xs font-black tracking-tighter">IC</span>
            </div>
            <a
                href="{{ route('inicio') }}"
                class="text-slate-900 font-bold text-lg tracking-wide
                       hover:text-slate-700 transition-colors whitespace-nowrap"
                aria-label="Ingecon — Volver al inicio"
            >
                INGECON
            </a>
        </div>

        {{-- ============================================================
             DERECHA — Links de sección + ícono candado (login)
             ============================================================ --}}
        <nav
            class="flex items-center gap-1"
            aria-label="Navegación principal"
        >
            {{-- Links de sección — visibles solo en pantallas medianas+ --}}
            <ul class="hidden md:flex items-center gap-1" role="list">

                <li>
                    <a
                        href="{{ route('inicio') }}"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors no-underline"
                    >
                        Inicio
                    </a>
                </li>

                <li>
                    <a
                        href="{{ route('inicio') }}#proyectos"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors no-underline"
                    >
                        Proyectos
                    </a>
                </li>

                <li>
                    <a
                        href="{{ route('inicio') }}#certificaciones"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors no-underline"
                    >
                        Certificaciones
                    </a>
                </li>

                <li>
                    <a
                        href="{{ route('inicio') }}#colaboradores"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors no-underline"
                    >
                        Colaboradores
                    </a>
                </li>

                <li>
                    <a
                        href="{{ route('inicio') }}#contacto"
                        class="px-3 py-1.5 text-sm font-medium text-slate-600
                               hover:text-slate-900 hover:bg-slate-100
                               rounded-lg transition-colors no-underline"
                    >
                        Contacto
                    </a>
                </li>

            </ul>

            {{-- Separador visual — solo en md+ --}}
            <div class="hidden md:block w-px h-5 bg-slate-200 mx-1" aria-hidden="true"></div>

            {{-- Ícono candado — abre modal de login (RF28 en 03_autenticacion.md) --}}
            <button
                type="button"
                @click="$dispatch('abrir-login')"
                class="p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100
                       transition-colors focus:outline-none focus-visible:ring-2
                       focus-visible:ring-slate-400"
                aria-label="Acceso al panel de administración"
                title="Acceso al panel de administración"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25
                             2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25
                             2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </button>

        </nav>

    </div>
</header>
