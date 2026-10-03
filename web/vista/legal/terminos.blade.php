<x-app-layout>
    <div class="bg-paper pt-32 pb-24">

        <header class="ig-container mb-16">
            <p class="ig-eyebrow mb-6">Marco legal</p>
            <h1 class="ig-h-display max-w-4xl mb-8">Términos, Condiciones y Política de Privacidad</h1>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-2">
                <p class="ig-meta">Última actualización · {{ now()->format('d/m/Y') }}</p>
                <span class="hidden md:block w-16 ig-rule !bg-line-strong" aria-hidden="true"></span>
                <p class="ig-meta">Documento público · Ingecon</p>
            </div>
        </header>

        <div class="ig-rule" aria-hidden="true"></div>

        <article class="ig-container max-w-[80rem]">
            <div class="grid grid-cols-12 gap-x-8">
                <div class="col-span-12 md:col-start-2 md:col-span-10 lg:col-start-2 lg:col-span-8">

                    <section class="pt-16">
                        <h2 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-5">1. Datos que solicitamos</h2>
                        <p class="ig-lede">
                            Al completar el Formulario de Contacto de este sitio, Ingecon recopila su nombre, apellido,
                            correo electrónico y el mensaje que usted ingresa. Estos datos se utilizan exclusivamente para
                            gestionar su consulta comercial y responderle a la brevedad.
                        </p>
                    </section>
                    <div class="ig-rule my-14" aria-hidden="true"></div>

                    <section>
                        <h2 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-5">2. Uso de la información</h2>
                        <p class="ig-lede">
                            La información entregada no se comparte con terceros ni se utiliza con fines distintos a la
                            gestión comercial de su solicitud. Los registros quedan almacenados en la base de datos interna de
                            Ingecon y son visibles únicamente para el Personal de Administración autorizado.
                        </p>
                    </section>
                    <div class="ig-rule my-14" aria-hidden="true"></div>

                    <section>
                        <h2 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-5">3. Conservación de datos</h2>
                        <p class="ig-lede">
                            Las consultas se conservan mientras sean necesarias para su gestión comercial. Usted puede
                            solicitar la eliminación de sus datos personales escribiendo a través del mismo Formulario de
                            Contacto.
                        </p>
                    </section>
                    <div class="ig-rule my-14" aria-hidden="true"></div>

                    <section>
                        <h2 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-5">4. Condiciones de uso del sitio</h2>
                        <p class="ig-lede">
                            El contenido publicado en este sitio (proyectos, certificaciones, información técnica) es
                            referencial y no constituye una oferta comercial vinculante. Los precios, plazos y condiciones de
                            cada proyecto se acuerdan directamente con el equipo comercial de Ingecon.
                        </p>
                    </section>
                    <div class="ig-rule my-14" aria-hidden="true"></div>

                    <section>
                        <h2 class="font-display text-2xl md:text-3xl font-light tracking-tight mb-5">5. Contacto</h2>
                        <p class="ig-lede">
                            Ante cualquier consulta sobre esta política, puede escribirnos a través del Formulario de
                            Contacto de la página de inicio.
                        </p>
                    </section>

                    <div class="mt-16 pt-8 border-t border-line">
                        <a href="/#contacto" class="ig-link-underline">&larr; Volver al formulario de contacto</a>
                    </div>

                </div>
            </div>
        </article>
    </div>
</x-app-layout>
