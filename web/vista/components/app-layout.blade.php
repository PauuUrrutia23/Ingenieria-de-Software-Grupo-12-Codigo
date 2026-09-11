<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Ingecon') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="antialiased font-sans text-[#1a1a1a] flex flex-col min-h-screen">

    <div x-data="{ isLateralOpen: false }">
      <button @click="isLateralOpen = true" aria-label="Abrir menú lateral"
              class="fixed top-6 left-4 z-[55] bg-white border border-gray-200 shadow-sm rounded-full p-2.5 hover:bg-gray-50 transition-colors">
        <i data-lucide="menu" class="h-5 w-5 text-[#1a1a1a]"></i>
      </button>

      <div x-show="isLateralOpen" @click="isLateralOpen = false"
           style="display: none;" class="fixed inset-0 bg-black/40 z-[60]"></div>

      <aside x-show="isLateralOpen"
             style="display: none;" class="fixed top-0 left-0 h-full w-72 bg-white z-[70] shadow-xl flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-200">
          <span class="font-bold text-lg text-[#1a1a1a]">Menú</span>
          <button @click="isLateralOpen = false" aria-label="Cerrar menú lateral" class="text-gray-500 hover:text-[#1a1a1a]">
            <i data-lucide="x" class="h-6 w-6"></i>
          </button>
        </div>
        <nav class="flex-1 p-4 space-y-1">
          <a href="/proyectos" class="flex items-center gap-3 px-3 py-3 rounded-md text-[#1a1a1a] font-semibold hover:bg-gray-50">
            <i data-lucide="hard-hat" class="h-5 w-5 text-[#28533c]"></i> Proyectos
          </a>
          <a href="{{ route('public.certificaciones') }}" class="flex items-center gap-3 px-3 py-3 rounded-md text-[#1a1a1a] font-semibold hover:bg-gray-50">
            <i data-lucide="shield-check" class="h-5 w-5 text-[#28533c]"></i> Certificaciones
          </a>
          <a href="{{ route('public.colaboradores') }}" class="flex items-center gap-3 px-3 py-3 rounded-md text-[#1a1a1a] font-semibold hover:bg-gray-50">
            <i data-lucide="handshake" class="h-5 w-5 text-[#28533c]"></i> Colaboradores
          </a>
        </nav>
      </aside>
    </div>

    <nav x-data="{ isMobileMenuOpen: false }" class="sticky top-0 z-50 bg-white border-b border-gray-200">
      <div class="max-w-[1200px] mx-auto px-4 lg:px-8">
        <div class="flex justify-between items-center h-[88px]">
          <a href="/" class="flex items-center gap-2 cursor-pointer ml-10">
            <i data-lucide="home" class="h-7 w-7 text-[#28533c] mr-2" stroke-width="2.5"></i>
            <span class="font-bold text-xl tracking-tight text-[#1a1a1a]">INGECON</span>
          </a>

          <div class="hidden md:flex items-center space-x-8">
            <a href="/" class="text-sm font-semibold pb-1 border-b-2 transition-colors {{ request()->is('/') ? 'text-[#1a1a1a] border-[#28533c]' : 'text-[#4a4a4a] border-transparent hover:text-[#1a1a1a]' }}">
                Nosotros
            </a>
            <a href="/#productos" class="text-sm font-semibold pb-1 border-b-2 border-transparent text-[#4a4a4a] hover:text-[#1a1a1a] transition-colors">
                Productos
            </a>
            <a href="/#proyectos" class="text-sm font-semibold pb-1 border-b-2 border-transparent text-[#4a4a4a] hover:text-[#1a1a1a] transition-colors">
                Proyectos
            </a>
            <a href="/#certificaciones" class="text-sm font-semibold pb-1 border-b-2 border-transparent text-[#4a4a4a] hover:text-[#1a1a1a] transition-colors">
                Certificaciones
            </a>
            <a href="{{ route('public.documentacion.conectores') }}" target="_blank" rel="noopener"
               class="text-sm font-semibold pb-1 border-b-2 border-transparent text-[#4a4a4a] hover:text-[#1a1a1a] transition-colors flex items-center gap-1">
                Conectores Metálicos <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
            </a>

            <a href="/#contacto" class="bg-[#28533c] text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-[#1e402e] transition-colors ml-2">
              Contáctanos
            </a>
          </div>

          <div class="md:hidden flex items-center">
            <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="text-[#1a1a1a] hover:text-[#28533c] focus:outline-none">
                <i data-lucide="menu" x-show="!isMobileMenuOpen" class="h-7 w-7"></i>
                <i data-lucide="x" x-show="isMobileMenuOpen" class="h-7 w-7" style="display: none;"></i>
            </button>
          </div>
        </div>
      </div>

      <div x-show="isMobileMenuOpen" style="display: none;" class="md:hidden bg-white border-t border-gray-100 absolute w-full shadow-lg">
        <div class="px-4 pt-2 pb-6 space-y-2">
            <a href="/" class="block w-full text-left px-3 py-3 text-base font-semibold text-[#4a4a4a] hover:text-[#28533c] hover:bg-gray-50 rounded-md">Nosotros</a>
            <a href="/#productos" @click="isMobileMenuOpen = false" class="block w-full text-left px-3 py-3 text-base font-semibold text-[#4a4a4a] hover:text-[#28533c] hover:bg-gray-50 rounded-md">Productos</a>
            <a href="/#proyectos" @click="isMobileMenuOpen = false" class="block w-full text-left px-3 py-3 text-base font-semibold text-[#4a4a4a] hover:text-[#28533c] hover:bg-gray-50 rounded-md">Proyectos</a>
            <a href="/#certificaciones" @click="isMobileMenuOpen = false" class="block w-full text-left px-3 py-3 text-base font-semibold text-[#4a4a4a] hover:text-[#28533c] hover:bg-gray-50 rounded-md">Certificaciones</a>
            <a href="{{ route('public.documentacion.conectores') }}" target="_blank" rel="noopener" class="block w-full text-left px-3 py-3 text-base font-semibold text-[#4a4a4a] hover:text-[#28533c] hover:bg-gray-50 rounded-md">Conectores Metálicos</a>
            <a href="/#contacto" @click="isMobileMenuOpen = false" class="block w-full text-center mt-4 bg-[#28533c] text-white px-5 py-3 rounded-md font-semibold">
              Contáctanos
            </a>
        </div>
      </div>
    </nav>

    <main class="flex-grow">
        {{ $slot }}
    </main>

    <footer class="bg-[#28533c] text-white py-12">
        <div class="max-w-[1200px] mx-auto px-4 lg:px-8 flex flex-col items-center">
            <div class="flex items-center mb-4">
                <i data-lucide="home" class="h-7 w-7 text-white mr-2" stroke-width="2.5"></i>
                <span class="font-bold text-xl tracking-tight">INGECON</span>
            </div>
            <a href="{{ route('terminos') }}" target="_blank" rel="noopener" class="text-sm text-[#d8e3dc] underline hover:text-white">Términos y Condiciones y Política de Privacidad</a>
            <p class="text-sm mt-4 text-[#d8e3dc]">&copy; {{ date('Y') }} Ingecon. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
