import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

// x-trap de las Ventanas Modales: mantiene el foco del teclado dentro del modal abierto.
Alpine.plugin(focus);

window.Alpine = Alpine;
Alpine.start();

/* ------------------------------------------------------------
   Capa de comportamiento del rediseño visual.
   No reemplaza ningún componente Alpine de las vistas: sólo
   revela por scroll, estado del navbar y preferencia de motion.
   ------------------------------------------------------------ */

const ELES_REVELADAS = '.ig-reveal';

function vigilarRevelados(raiz = document) {
    if (!('IntersectionObserver' in window)) {
        raiz.querySelectorAll(ELES_REVELADAS).forEach((el) => el.classList.add('is-in'));
        return;
    }

    const observador = new IntersectionObserver((entradas, obs) => {
        entradas.forEach((entrada) => {
            if (!entrada.isIntersecting) return;
            entrada.target.classList.add('is-in');
            obs.unobserve(entrada.target); // anima una sola vez
        });
    }, { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.15 });

    raiz.querySelectorAll(ELES_REVELADAS).forEach((el) => {
        if (!el.classList.contains('is-in')) observador.observe(el);
    });
}

/* El nav público va transparente sobre el hero y se vuelve sólido
   al scrollear. En páginas sin hero nace ya sólido. */
function vigilarNavbar() {
    const nav = document.querySelector('.ig-nav');
    if (!nav) return;

    const tieneHero = document.querySelector('[data-ig-hero-overlay]') !== null;
    const umbral = 40;

    const actualizar = () => {
        const solido = !tieneHero || window.scrollY > umbral;
        if (solido) nav.setAttribute('data-ig-solid', '');
        else nav.removeAttribute('data-ig-solid');
    };

    actualizar();

    let pendiente = false;
    window.addEventListener('scroll', () => {
        if (pendiente) return;
        pendiente = true;
        requestAnimationFrame(() => {
            pendiente = false;
            actualizar();
        });
    }, { passive: true });
}

/* Este equipo reporta prefers-reduced-motion: reduce, así que el
   motion se ve apagado por sistema. La píldora del footer lo
   enciende sin tocar la configuración de Windows. */
const CLAVE_MOTION = 'ig-motion';

function aplicarPreferenceMotion() {
    if (localStorage.getItem(CLAVE_MOTION) === 'on') {
        document.documentElement.classList.add('force-motion');
    }
    sincronizarPillMotion();
}

function sincronizarPillMotion() {
    const activo = document.documentElement.classList.contains('force-motion');
    document.querySelectorAll('[data-ig-motion-toggle]').forEach((boton) => {
        boton.textContent = activo ? 'Ocultar animaciones' : 'Ver animaciones';
        boton.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
}

function vigilarPillMotion() {
    document.querySelectorAll('[data-ig-motion-toggle]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const activo = document.documentElement.classList.toggle('force-motion');
            localStorage.setItem(CLAVE_MOTION, activo ? 'on' : 'off');
            sincronizarPillMotion();
            if (activo) vigilarRevelados();
        });
    });
}

/* El contenido dinámico (filtros de proyectos) se inyecta con
   innerHTML: sus elementos nuevos necesitan observarse y sus
   iconos Lucide, regenerarse. */
function refrescarContenidoDinamico(raiz) {
    vigilarRevelados(raiz);
    if (window.lucide) window.lucide.createIcons();
}

window.igRefrescarContenidoDinamico = refrescarContenidoDinamico;

aplicarPreferenceMotion();
vigilarNavbar();
vigilarRevelados();
vigilarPillMotion();
