@php
    // Versión 4 revisada por el equipo legal. Las cláusulas se escriben aquí tal como fueron aprobadas.
    $correo = config('app.correo_contacto');
    $responsable = 'Guillermo Enrique Cáceres Vergara';
    $domicilio = 'Avenida General Velásquez 10.100, San Bernardo, Región Metropolitana';
    $h2 = 'font-display text-3xl md:text-4xl font-light tracking-tight mb-10';
    $h3 = 'font-display text-xl md:text-2xl font-light tracking-tight mb-4';
    $lista = 'ig-lede list-disc pl-6 space-y-2 mt-4';
@endphp
<x-app-layout>
    <div class="bg-paper pt-32 pb-24">

        <header class="ig-container mb-16">
            <p class="ig-eyebrow mb-6">Marco legal</p>
            <h1 class="ig-h-display max-w-4xl mb-8">Términos, Condiciones y Política de Privacidad</h1>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-2">
                <p class="ig-meta">Versión 4 · 11 de noviembre de 2026</p>
                <span class="hidden md:block w-16 ig-rule !bg-line-strong" aria-hidden="true"></span>
                <p class="ig-meta">Documento público · Ingecon</p>
            </div>
        </header>

        <div class="ig-rule" aria-hidden="true"></div>

        <article class="ig-container max-w-[80rem]">
            <div class="grid grid-cols-12 gap-x-8">
                <div class="col-span-12 md:col-start-2 md:col-span-10 lg:col-start-2 lg:col-span-8 space-y-10">

                    <h2 class="{{ $h2 }} pt-16">Parte I — Términos y Condiciones de uso del sitio</h2>

                    <section>
                        <h3 class="{{ $h3 }}">1. Quién administra el sitio</h3>
                        <p class="ig-lede">Este sitio web, www.ingeconltda.cl, es administrado por {{ $responsable }}, con domicilio en {{ $domicilio }}, en adelante «Ingecon».</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">2. Aceptación</h3>
                        <p class="ig-lede">Al navegar por el sitio, el usuario acepta estos Términos y Condiciones. Para enviar una consulta, el Formulario de Contacto exige marcar expresamente la casilla de aceptación de estos Términos y de la Política de Privacidad; sin ella, la consulta no se envía.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">3. Objeto del sitio</h3>
                        <p class="ig-lede">El sitio informa sobre la empresa, sus productos, sus proyectos ejecutados, sus certificaciones y sus colaboradores, y permite enviar consultas comerciales. El sitio no vende productos ni servicios en línea.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">4. Carácter referencial del contenido</h3>
                        <p class="ig-lede">La información publicada (proyectos, fichas técnicas, certificaciones, imágenes) es referencial y no constituye una oferta comercial vinculante. Precios, plazos, especificaciones y condiciones se acuerdan directamente con el equipo comercial de Ingecon para cada proyecto.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">5. Propiedad intelectual</h3>
                        <p class="ig-lede">Los textos, fotografías, logotipos, fichas técnicas y diseño del sitio pertenecen a Ingecon o se usan con autorización de sus titulares. Los logotipos de colaboradores y organismos certificadores pertenecen a sus respectivos dueños. No se permite copiar, reproducir ni distribuir este contenido con fines comerciales sin autorización escrita de Ingecon.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">6. Enlaces a sitios de terceros</h3>
                        <p class="ig-lede">El sitio contiene enlaces a sitios externos, como los de colaboradores, organismos certificadores, Google Maps y la documentación técnica. Ingecon no controla esos sitios ni responde por su contenido ni por sus políticas de privacidad.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">7. Responsabilidad</h3>
                        <p class="ig-lede">Ingecon procura que la información del sitio sea correcta y esté actualizada, pero no garantiza que el sitio esté disponible sin interrupciones, porque funciona en un servicio de alojamiento externo. Ingecon no responde por daños derivados del uso de la información referencial del sitio sin la confirmación de su equipo comercial.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">8. Modificaciones</h3>
                        <p class="ig-lede">Ingecon puede modificar estos Términos y la Política de Privacidad. La versión vigente es la publicada en el sitio, con su fecha de versión. Los cambios no afectan las consultas ya enviadas bajo una versión anterior.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">9. Ley aplicable y tribunales</h3>
                        <p class="ig-lede">Estos Términos se rigen por las leyes de la República de Chile. Cualquier controversia se somete a los tribunales ordinarios de justicia de Santiago, sin perjuicio de los derechos que la ley reconoce a los consumidores.</p>
                    </section>

                    <div class="ig-rule my-14" aria-hidden="true"></div>

                    <h2 class="{{ $h2 }}">Parte II — Política de Privacidad</h2>

                    <section>
                        <h3 class="{{ $h3 }}">1. Responsable del tratamiento</h3>
                        <p class="ig-lede">El responsable de los datos personales que se reciben por este sitio es {{ $responsable }}, con domicilio en {{ $domicilio }}.</p>
                        <p class="ig-lede mt-4">Para cualquier asunto sobre sus datos personales, puede escribir a <a href="mailto:{{ $correo }}" class="underline hover:text-wood-deep transition-colors">{{ $correo }}</a>. Este correo aparece también en el pie de página del sitio.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">2. Datos que tratamos</h3>
                        <p class="ig-lede">Cuando usted envía el Formulario de Contacto, Ingecon recibe:</p>
                        <ul class="{{ $lista }}">
                            <li>Su nombre (obligatorio) y su apellido (opcional).</li>
                            <li>Su correo electrónico.</li>
                            <li>El mensaje que escribe.</li>
                            <li>La fecha y hora del envío, y la constancia de que aceptó estos Términos y esta Política.</li>
                        </ul>
                        <p class="ig-lede mt-4">El sitio no pide RUT, teléfono, dirección ni datos sensibles. Le pedimos no incluir datos sensibles en el mensaje.</p>
                        <p class="ig-lede mt-4">Al navegar sin enviar el formulario, el sitio no registra datos que lo identifiquen, vea las secciones 8 y 9 sobre cookies y dirección IP.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">3. Para qué usamos los datos y con qué base legal</h3>
                        <p class="ig-lede">Usamos los datos únicamente para recibir, gestionar y responder su consulta comercial. La base legal es su consentimiento, que usted otorga al marcar la casilla de aceptación antes de enviar el formulario. Puede retirarlo en cualquier momento por los medios de la sección 1, sin efecto retroactivo.</p>
                        <p class="ig-lede mt-4">No usamos sus datos para publicidad, perfiles ni decisiones automatizadas, y no los vendemos ni los cedemos.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">4. Quién accede a los datos</h3>
                        <ul class="{{ $lista }}">
                            <li><strong class="font-medium">Personal de Ingecon:</strong> solo el personal autorizado del equipo comercial y de administración, con cuenta personal y permisos asignados por la empresa.</li>
                            <li><strong class="font-medium">Proveedor de alojamiento:</strong> el sitio y su base de datos funcionan en los servidores de hostname.cl, que actúa como encargado del tratamiento y no usa los datos para fines propios.</li>
                            <li><strong class="font-medium">Correo electrónico:</strong> al recibir su consulta, el sistema le envía un correo de confirmación y envía un aviso con su consulta a los correos corporativos del equipo de Ingecon.</li>
                        </ul>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">5. Cuánto tiempo conservamos los datos</h3>
                        <p class="ig-lede">El sistema elimina las consultas automáticamente, sin intervención de una persona:</p>
                        <ul class="{{ $lista }}">
                            <li>30 días después de que la consulta queda marcada como «Finalizada».</li>
                            <li>6 meses después de su último cambio de estado, si no se finalizó antes.</li>
                        </ul>
                        <p class="ig-lede mt-4">Al eliminar la consulta se eliminan también su nombre, apellido y correo, salvo que usted tenga otras consultas vigentes. Las copias de seguridad de la base de datos se borran a los 30 días según el requerimiento del sistema, por lo que un dato eliminado puede permanecer en ellas hasta ese plazo.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">6. Sus derechos</h3>
                        <p class="ig-lede">Usted puede solicitar en cualquier momento:</p>
                        <ul class="{{ $lista }}">
                            <li><strong class="font-medium">Acceso:</strong> saber qué datos suyos tenemos.</li>
                            <li><strong class="font-medium">Rectificación:</strong> corregir datos inexactos.</li>
                            <li><strong class="font-medium">Supresión:</strong> eliminar sus datos antes de los plazos de la sección 5.</li>
                            <li><strong class="font-medium">Oposición:</strong> oponerse a un tratamiento determinado.</li>
                            <li><strong class="font-medium">Portabilidad:</strong> recibir sus datos en un formato de uso común.</li>
                            <li><strong class="font-medium">Bloqueo:</strong> suspender temporalmente el tratamiento.</li>
                        </ul>
                        <p class="ig-lede mt-4">Para ejercerlos, escriba a <a href="mailto:{{ $correo }}" class="underline hover:text-wood-deep transition-colors">{{ $correo }}</a>, indicando el correo con que envió su consulta. Responderemos en un plazo máximo de 30 días corridos. Si su solicitud no es atendida, puede reclamar ante la Agencia de Protección de Datos Personales.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">7. Seguridad</h3>
                        <ul class="{{ $lista }}">
                            <li>El sitio funciona con conexión cifrada (HTTPS).</li>
                            <li>Solo el personal autorizado entra al panel de administración, con contraseña cifrada y bloqueo tras intentos fallidos.</li>
                            <li>Los registros de errores del sistema no guardan nombres, correos ni mensajes.</li>
                            <li>El formulario limita los envíos repetidos para evitar abusos.</li>
                        </ul>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">8. Cookies</h3>
                        <p class="ig-lede">El sitio usa solo cookies técnicas, necesarias para su funcionamiento: una cookie de sesión y una cookie de seguridad que protege los formularios contra envíos falsificados. Expiran tras 2 horas sin actividad en el sitio. El sitio no usa cookies de análisis, publicidad ni seguimiento.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">9. Dirección IP y mapa</h3>
                        <ul class="{{ $lista }}">
                            <li><strong class="font-medium">Formulario de Contacto:</strong> para evitar envíos masivos, el sitio cuenta los envíos hechos desde una misma dirección IP en la última hora. La IP se usa solo para ese control, no se guarda junto a su consulta y se descarta en un plazo máximo de 24 horas.</li>
                            <li><strong class="font-medium">Fuentes y librerías:</strong> las tipografías, íconos y librerías del sitio se cargan desde el propio servidor de Ingecon, sin contactar a terceros.</li>
                            <li><strong class="font-medium">Mapa de proyectos:</strong> el mapa interactivo de la página de Proyectos obtiene sus imágenes de OpenStreetMap. Al verlo, su navegador envía su dirección IP a ese servicio, que se rige por su propia política de privacidad.</li>
                        </ul>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">10. Menores de edad</h3>
                        <p class="ig-lede">El sitio está dirigido a empresas y personas adultas interesadas en los servicios de Ingecon. No está pensado para menores de edad.</p>
                    </section>
                    <section>
                        <h3 class="{{ $h3 }}">11. Cambios a esta política</h3>
                        <p class="ig-lede">Si esta política cambia, la nueva versión se publica en el sitio con su fecha. Si el cambio afecta la finalidad o los plazos de conservación, se aplicará solo a las consultas enviadas después de la nueva versión.</p>
                    </section>

                    <div class="mt-16 pt-8 border-t border-line">
                        <a href="/#contacto" class="ig-link-underline">&larr; Volver al formulario de contacto</a>
                    </div>

                </div>
            </div>
        </article>
    </div>
</x-app-layout>
