@extends('layouts.admin')

@section('title', 'Dashboard — Panel Ingecon')

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Panel de Gestion</h1>
    <p class="text-slate-500 text-sm mt-0.5">
        Bienvenido al panel de administracion de Ingecon.
    </p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
    <a href="{{ route('admin.proyectos.panel') }}"
       class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100
              hover:shadow-md transition-shadow group">
        <div class="flex items-center gap-4 mb-3">
            <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5
                             3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125
                             1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-slate-900 text-lg group-hover:text-slate-700 transition-colors">
                    Proyectos
                </h2>
                <p class="text-slate-500 text-sm">Gestiona el portafolio de obras</p>
            </div>
        </div>
    </a>

    <a href="{{ route('admin.colaboradores.panel') }}"
       class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100
              hover:shadow-md transition-shadow group">
        <div class="flex items-center gap-4 mb-3">
            <div class="w-12 h-12 bg-slate-900 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94
                             3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112
                             21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12
                             0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995
                             5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0
                             003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197"/>
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-slate-900 text-lg group-hover:text-slate-700 transition-colors">
                    Colaboradores
                </h2>
                <p class="text-slate-500 text-sm">Empresas y marcas asociadas</p>
            </div>
        </div>
    </a>
</div>

@endsection
