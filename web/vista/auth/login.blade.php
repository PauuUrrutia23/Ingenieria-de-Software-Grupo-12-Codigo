<x-app-layout>
    <div class="relative min-h-[70vh] bg-[#f5f3ec]"
         x-data="{ acceso: true, olvide: false }">

        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 py-24 text-center">
            <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">ACCESO INTERNO</p>
            <h1 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] tracking-tight mb-4">Panel de Gestión</h1>
            <p class="text-[#666666] mb-8">Área exclusiva del Personal de Administración de Ingecon.</p>
            <button type="button" @click="acceso = true"
                    class="bg-slate-900 text-white px-8 py-3.5 rounded-xl text-sm font-semibold hover:bg-slate-700 transition-colors">
                Iniciar sesión
            </button>
        </div>

        <div x-show="acceso" style="display:none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-[80] flex items-center justify-center px-4 py-8"
             @keydown.escape.window="acceso = false"
             role="dialog" aria-modal="true" aria-labelledby="acceso-titulo">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="acceso = false" aria-hidden="true"></div>

            <div @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative max-w-md w-full bg-white p-8 rounded-2xl shadow-2xl">

                <button type="button" @click="acceso = false" aria-label="Cerrar ventana de acceso"
                        class="absolute top-4 right-4 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="text-center mb-8">
                    <div class="mx-auto w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center mb-4">
                        <i data-lucide="lock" class="h-6 w-6 text-white"></i>
                    </div>
                    <h2 id="acceso-titulo" class="text-2xl font-bold text-slate-900">Acceso al Panel</h2>
                    <p class="mt-2 text-sm text-slate-500">Ingrese sus credenciales de administración</p>
                </div>

                @if (session('success'))
                    <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl mb-6 text-sm font-medium border border-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm font-medium border border-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form class="space-y-5" action="/login" method="POST">
                    @csrf
                    <div>
                        <label for="correo" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo Electrónico</label>
                        <input id="correo" name="correo" type="email" value="{{ old('correo') }}" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" placeholder="admin@ingecon.cl">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña</label>
                        <input id="password" name="password" type="password" required
                               class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" placeholder="••••••••">
                    </div>
                    <div class="text-right">
                        <button type="button" @click="acceso = false; olvide = true" class="text-sm font-semibold text-slate-600 hover:text-slate-900 hover:underline">¿Olvidó su contraseña?</button>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-8 py-2.5 rounded-lg transition-colors">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="olvide" style="display:none;"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-[80] flex items-center justify-center px-4"
             @keydown.escape.window="olvide = false"
             role="dialog" aria-modal="true" aria-labelledby="recuperar-titulo">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="olvide = false" aria-hidden="true"></div>

            <div @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative max-w-md w-full bg-white p-8 rounded-2xl shadow-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 id="recuperar-titulo" class="text-xl font-bold text-slate-900">Recuperar contraseña</h3>
                    <button type="button" @click="olvide = false; acceso = true" aria-label="Cerrar"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <p class="text-sm text-slate-500 mb-5">Ingrese su correo institucional y le enviaremos un enlace para restablecer su contraseña.</p>
                <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                    @csrf
                    <label for="correo-recuperacion" class="sr-only">Correo institucional</label>
                    <input id="correo-recuperacion" type="email" name="correo" required
                           class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" placeholder="admin@ingecon.cl">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-8 py-2.5 rounded-lg transition-colors">
                        Enviar enlace de recuperación
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
