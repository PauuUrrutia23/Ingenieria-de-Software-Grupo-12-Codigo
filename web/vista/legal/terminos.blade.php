<x-app-layout>
    <div class="max-w-[860px] mx-auto px-4 lg:px-8 py-16">
        <p class="text-[#c66f4b] font-bold text-xs tracking-[0.15em] uppercase mb-3">MARCO LEGAL</p>
        <h1 class="text-3xl md:text-4xl font-bold text-[#1a1a1a] mb-8 tracking-tight">Términos, Condiciones y Política de Privacidad</h1>

        <div class="prose max-w-none text-[#4a4a4a] leading-relaxed space-y-6">
            <p>Última actualización: {{ now()->format('d/m/Y') }}.</p>

            <section>
                <h2 class="text-xl font-bold text-[#1a1a1a] mt-8 mb-3">1. Datos que solicitamos</h2>
                <p>Al completar el Formulario de Contacto de este sitio, Ingecon recopila su nombre, apellido,
                correo electrónico y el mensaje que usted ingresa. Estos datos se utilizan exclusivamente para
                gestionar su consulta comercial y responderle a la brevedad.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#1a1a1a] mt-8 mb-3">2. Uso de la información</h2>
                <p>La información entregada no se comparte con terceros ni se utiliza con fines distintos a la
                gestión comercial de su solicitud. Los registros quedan almacenados en la base de datos interna de
                Ingecon y son visibles únicamente para el Personal de Administración autorizado.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#1a1a1a] mt-8 mb-3">3. Conservación de datos</h2>
                <p>Las consultas se conservan mientras sean necesarias para su gestión comercial. Usted puede
                solicitar la eliminación de sus datos personales escribiendo a través del mismo Formulario de
                Contacto.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#1a1a1a] mt-8 mb-3">4. Condiciones de uso del sitio</h2>
                <p>El contenido publicado en este sitio (proyectos, certificaciones, información técnica) es
                referencial y no constituye una oferta comercial vinculante. Los precios, plazos y condiciones de
                cada proyecto se acuerdan directamente con el equipo comercial de Ingecon.</p>
            </section>

            <section>
                <h2 class="text-xl font-bold text-[#1a1a1a] mt-8 mb-3">5. Contacto</h2>
                <p>Ante cualquier consulta sobre esta política, puede escribirnos a través del Formulario de
                Contacto de la página de inicio.</p>
            </section>
        </div>

        <a href="/#contacto" class="inline-block mt-10 text-[#28533c] font-semibold hover:underline">&larr; Volver al formulario de contacto</a>
    </div>
</x-app-layout>
