import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

window.Alpine = Alpine;
Alpine.start();

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
            obs.unobserve(entrada.target);
        });
    }, { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.15 });

    raiz.querySelectorAll(ELES_REVELADAS).forEach((el) => {
        if (!el.classList.contains('is-in')) observador.observe(el);
    });
}

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

// El contenido que se inyecta al filtrar proyectos necesita volver a observarse y regenerar sus íconos.
function refrescarContenidoDinamico(raiz) {
    vigilarRevelados(raiz);
    if (window.lucide) window.lucide.createIcons();
}

window.igRefrescarContenidoDinamico = refrescarContenidoDinamico;

vigilarNavbar();
vigilarRevelados();
