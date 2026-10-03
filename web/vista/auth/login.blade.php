<x-app-layout>
    <div x-data="{ acceso: true, olvide: false }">

        <section data-ig-hero-overlay
                 class="relative min-h-screen bg-carbon ig-blueprint overflow-hidden">

            <div class="absolute inset-y-0 right-0 w-full lg:w-1/2 pointer-events-none" aria-hidden="true">
                <svg class="absolute inset-0 w-full h-full opacity-60"
                     viewBox="0 0 640 520" fill="none" preserveAspectRatio="xMidYMid slice">
                    <line x1="40" y1="140" x2="600" y2="140" stroke="rgba(217,185,143,0.30)" stroke-width="1"/>
                    <line x1="40" y1="300" x2="600" y2="300" stroke="rgba(217,185,143,0.30)" stroke-width="1"/>
                    <path d="M40 220 L120 140 L200 220 L280 140 L360 220 L440 140 L520 220 L600 140"
                          stroke="rgba(255,255,255,0.35)" stroke-width="1" fill="none"/>
                    <line x1="120" y1="140" x2="120" y2="300" stroke="rgba(255,255,255,0.18)" stroke-width="1"/>
                    <line x1="280" y1="140" x2="280" y2="300" stroke="rgba(255,255,255,0.18)" stroke-width="1"/>
                    <line x1="440" y1="140" x2="440" y2="300" stroke="rgba(255,255,255,0.18)" stroke-width="1"/>
                    <line x1="600" y1="140" x2="600" y2="300" stroke="rgba(255,255,255,0.18)" stroke-width="1"/>
                    <path d="M40 300 L40 420 M600 300 L600 420" stroke="rgba(255,255,255,0.28)" stroke-width="1"/>
                    <path d="M20 420 L60 420 M580 420 L620 420" stroke="rgba(217,185,143,0.40)" stroke-width="1"/>
                    <line x1="40" y1="360" x2="600" y2="360" stroke="rgba(255,255,255,0.14)" stroke-width="1" stroke-dasharray="2 6"/>
                    <circle cx="120" cy="140" r="3" stroke="rgba(217,185,143,0.55)" stroke-width="1" fill="none"/>
                    <circle cx="280" cy="140" r="3" stroke="rgba(217,185,143,0.55)" stroke-width="1" fill="none"/>
                    <circle cx="440" cy="140" r="3" stroke="rgba(217,185,143,0.55)" stroke-width="1" fill="none"/>
                    <circle cx="600" cy="140" r="3" stroke="rgba(217,185,143,0.55)" stroke-width="1" fill="none"/>
                    <rect x="40" y="60" width="560" height="400" stroke="rgba(255,255,255,0.10)" stroke-width="1" fill="none"/>
                    <text x="46" y="82" fill="rgba(255,255,255,0.35)" font-family="IBM Plex Mono, monospace" font-size="10" letter-spacing="2">E-01 · CERCHA</text>
                    <text x="46" y="452" fill="rgba(255,255,255,0.35)" font-family="IBM Plex Mono, monospace" font-size="10" letter-spacing="2">ESC. 1:50</text>
                </svg>
                <p class="absolute bottom-8 right-8 ig-meta !text-white/40">Plano de taller · Ingecon</p>
            </div>

            <div class="relative ig-container min-h-screen flex items-center">
                <div class="max-w-xl w-full pt-32 pb-24">
                    <p class="ig-eyebrow ig-eyebrow-on-dark mb-6">Acceso interno</p>
                    <h1 class="ig-h-display text-white mb-5">Panel de Gestión</h1>
                    <p class="ig-lede !text-white/65 mb-10">
                        Área exclusiva del Personal de Administración de Ingecon.
                    </p>
                    <button type="button" @click="acceso = true"
                            class="ig-btn ig-btn-hero">
                        <span class="ig-btn-label">
                            Iniciar sesión
                            <i data-lucide="arrow-right" class="ig-btn-arrow h-4 w-4"></i>
                        </span>
                    </button>
                    <p class="mt-12 ig-meta !text-white/40">
                        Industrialización de la madera · desde 1994
                    </p>
                </div>
            </div>
        </section>

        <div x-show="acceso" style="display:none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[80]"
             @keydown.escape.window="acceso = false"
             role="dialog" aria-modal="true" aria-labelledby="acceso-titulo">

            <div class="absolute inset-0 bg-carbon/60 backdrop-blur-[2px]" @click="acceso = false" aria-hidden="true"></div>

            <div @click.stop
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-8 opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 class="relative h-full w-full lg:w-[50%] bg-surface border-l border-line overflow-y-auto">

                <button type="button" @click="acceso = false" aria-label="Cerrar ventana de acceso"
                        class="absolute top-5 right-5 p-2 text-mute hover:text-carbon transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>

                <div class="min-h-full flex flex-col justify-center px-6 sm:px-12 lg:px-16 py-16">
                    <p class="ig-eyebrow mb-6">Acceso interno</p>
                    <h2 id="acceso-titulo" class="ig-h-section mb-2">Acceso al Panel</h2>
                    <p class="text-sm text-mute mb-10">Ingrese sus credenciales de administración.</p>

                    @if (session('success'))
                        <div class="mb-8 border border-[#3f6b4a]/40 bg-[#f0f5f1] px-4 py-3 text-sm font-medium text-[#33573d]" role="status">
                            <span class="flex items-start gap-2">
                                <i data-lucide="check-circle-2" class="h-4 w-4 mt-0.5 shrink-0"></i>
                                {{ session('success') }}
                            </span>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-8 border border-[#b4403f]/40 bg-[#f8f1f0] px-4 py-3 text-sm font-medium text-[#8c2f2f]" role="alert">
                            <span class="flex items-start gap-2">
                                <i data-lucide="alert-triangle" class="h-4 w-4 mt-0.5 shrink-0"></i>
                                {{ $errors->first() }}
                            </span>
                        </div>
                    @endif

                    <form class="space-y-7" action="/login" method="POST">
                        @csrf
                        <div>
                            <label for="correo" class="ig-label">Correo Electrónico</label>
                            <input id="correo" name="correo" type="email" value="{{ old('correo') }}" required
                                   class="ig-input" placeholder="admin@ingecon.cl" autocomplete="username">
                        </div>
                        <div>
                            <label for="password" class="ig-label">Contraseña</label>
                            <input id="password" name="password" type="password" required
                                   class="ig-input" placeholder="••••••••" autocomplete="current-password">
                        </div>
                        <div class="flex items-center justify-between gap-4 pt-1">
                            <button type="button" @click="acceso = false; olvide = true"
                                    class="text-xs font-medium tracking-[0.08em] text-wood-deep hover:text-carbon transition-colors">
                                ¿Olvidó su contraseña?
                            </button>
                            <button type="submit" class="ig-btn ig-btn-primary">
                                Iniciar Sesión
                            </button>
                        </div>
                    </form>

                    <div class="mt-14 pt-8 border-t border-line">
                        <p class="ig-meta">Soporte · Personal de Administración</p>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="olvide" style="display:none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-[80]"
             @keydown.escape.window="olvide = false"
             role="dialog" aria-modal="true" aria-labelledby="recuperar-titulo">

            <div class="absolute inset-0 bg-carbon/60 backdrop-blur-[2px]" @click="olvide = false" aria-hidden="true"></div>

            <div @click.stop
                 class="relative h-full w-full lg:w-[50%] bg-surface border-l border-line overflow-y-auto flex flex-col justify-center">

                <div class="px-6 sm:px-12 lg:px-16 py-16">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <p class="ig-eyebrow">Recuperación</p>
                        <button type="button" @click="olvide = false; acceso = true" aria-label="Cerrar"
                                class="p-2 text-mute hover:text-carbon transition-colors">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                    <h3 id="recuperar-titulo" class="ig-h-section mb-2">Recuperar contraseña</h3>
                    <p class="text-sm text-mute mb-10 max-w-sm">
                        Ingrese su correo institucional y le enviaremos un enlace para restablecer su contraseña.
                    </p>

                    <form action="{{ route('password.email') }}" method="POST" class="space-y-7">
                        @csrf
                        <div>
                            <label for="correo-recuperacion" class="ig-label">Correo institucional</label>
                            <input id="correo-recuperacion" name="correo" type="email" required
                                   class="ig-input" placeholder="admin@ingecon.cl" autocomplete="username">
                        </div>
                        <button type="submit" class="w-full ig-btn ig-btn-primary">
                            Enviar enlace de recuperación
                        </button>
                    </form>

                    <p class="mt-10 text-xs text-mute font-light">
                        El enlace vence en 60 minutos y solo puede usarse una vez.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
