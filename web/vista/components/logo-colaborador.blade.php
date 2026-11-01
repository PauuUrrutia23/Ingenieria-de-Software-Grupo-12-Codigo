@props(['colaborador'])

{{-- RF13 / CU 13.1: el logo abre el Sitio Web del Colaborador en una nueva pestaña solo si está registrado. --}}
@if($colaborador->sitio_web)
    <a href="{{ $colaborador->sitio_web }}" target="_blank" rel="noopener noreferrer"
       aria-label="Sitio web de {{ $colaborador->nombre_comercial }} (se abre en una nueva pestaña)"
       class="flex items-center justify-center max-h-full max-w-full">
        <img src="{{ $colaborador->logo_url }}" alt="{{ $colaborador->nombre_comercial }}" loading="lazy" {{ $attributes }}>
    </a>
@else
    <img src="{{ $colaborador->logo_url }}" alt="{{ $colaborador->nombre_comercial }}" loading="lazy" {{ $attributes }}>
@endif
