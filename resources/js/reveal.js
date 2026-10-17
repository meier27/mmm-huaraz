/**
 * Aparición suave al hacer scroll (sitio público).
 *
 * - Solo anima lo que aún está fuera de la pantalla al cargar: lo visible se
 *   queda quieto (sin parpadeo).
 * - Sin JavaScript, o con «reducir movimiento», todo se ve desde el principio.
 * - No toca nada dentro de componentes Livewire: al re-renderizar quitarían la
 *   clase y el contenido quedaría oculto.
 */
const SELECTORS = [
    'main h2',
    'main h2 + p',
    'main [data-reveal]',
    'main ul > li',
    'main ol > li',
    'main article',
    'main figure',
].join(',');

const EXCLUDED = '[wire\\:id], header, footer, nav, [role="tablist"], [aria-roledescription="carrusel"], dl';

function setup() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        }
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    const fold = window.innerHeight;
    const targets = [...document.querySelectorAll(SELECTORS)].filter((el) => !el.closest(EXCLUDED));

    for (const el of targets) {
        // Ya animado por un contenedor que también se revela, o visible al cargar.
        if (el.parentElement?.closest('.reveal') || el.getBoundingClientRect().top < fold) {
            continue;
        }

        // Escalonado entre hermanos de una misma lista (máx. 5 pasos).
        if (el.tagName === 'LI') {
            const index = [...el.parentElement.children].indexOf(el) % 5;
            el.style.setProperty('--reveal-delay', `${index * 130}ms`);
        }

        el.classList.add('reveal');
        observer.observe(el);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setup);
} else {
    setup();
}
