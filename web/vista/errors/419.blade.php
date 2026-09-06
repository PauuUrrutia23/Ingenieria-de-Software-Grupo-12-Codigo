{{-- RNF10: el 419 de Laravel ("Page Expired") es un código HTTP en inglés.
     La página caduca cuando el formulario queda abierto más que la sesión
     (SESSION_LIFETIME, 120 minutos): el token CSRF deja de ser válido y el
     envío se rechaza. Es la protección funcionando, no un error del Usuario. --}}
<x-app-layout>
    <div class="flex items-center justify-center min-h-[60vh] bg-gray-50">
        <div class="text-center max-w-lg px-6">
            <p class="text-2xl font-semibold text-gray-700 mb-6">La página estuvo abierta demasiado tiempo</p>
            <p class="text-gray-500 mb-8">
                Por seguridad, el formulario caduca tras un periodo de inactividad.
                Vuelva a abrirlo e ingrese los datos nuevamente.
            </p>
            <a href="{{ url()->previous() }}"
               class="bg-[#28533c] text-white px-6 py-3 rounded font-bold hover:bg-[#1e402e] transition">
                Volver a intentarlo
            </a>
        </div>
    </div>
</x-app-layout>
