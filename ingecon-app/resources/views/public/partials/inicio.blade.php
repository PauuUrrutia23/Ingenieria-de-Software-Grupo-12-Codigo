{{--
    Sección Inicio — Hero público de Ingecon
    Contenido dinámico: pendiente de incremento de portafolio
--}}

<div class="text-center max-w-4xl mx-auto">

    <span class="inline-block px-4 py-1.5 bg-slate-900 text-white text-xs font-semibold
                 rounded-full uppercase tracking-widest mb-6">
        Empresa Constructora Chilena
    </span>

    <h1
        id="inicio-titulo"
        class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900
               leading-tight tracking-tight mb-6"
    >
        Construimos el
        <span class="text-slate-500">Chile</span>
        de mañana
    </h1>

    <p class="text-slate-600 text-lg sm:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
        Más de una década de experiencia en infraestructura vial, obras civiles
        e instalaciones industriales a lo largo de todo el país.
    </p>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <button
            type="button"
            @click="document.getElementById('proyectos').scrollIntoView({ behavior: 'smooth' })"
            class="px-7 py-3 bg-slate-900 hover:bg-slate-700 text-white font-semibold
                   text-sm rounded-xl transition-colors"
        >
            Ver proyectos
        </button>
        <button
            type="button"
            @click="document.getElementById('contacto').scrollIntoView({ behavior: 'smooth' })"
            class="px-7 py-3 bg-white hover:bg-slate-50 text-slate-900 font-semibold
                   text-sm rounded-xl border border-slate-200 transition-colors"
        >
            Contactar
        </button>
    </div>

</div>
