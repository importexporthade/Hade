import { initCarousels } from './carousel';
import { initCart } from './cart';
import { initGalleries } from './gallery';
import { initHeroSliders } from './hero-slider';
import { initMoodShowcases } from './mood-showcase';

function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');
    const panel = document.querySelector('[data-menu-panel]');

    if (!toggle || !menu || !panel) {
        return;
    }

    const isOpen = () => panel.dataset.open === 'true';

    const setOpen = (open) => {
        panel.dataset.open = String(open);
        panel.setAttribute('aria-hidden', String(!open));
        panel.inert = !open;
        toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => {
        setOpen(!isOpen());
    });

    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (!isOpen() || toggle.contains(event.target) || panel.contains(event.target)) {
            return;
        }

        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !isOpen()) {
            return;
        }

        setOpen(false);
        toggle.focus();
    });

    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches) {
            setOpen(false);
        }
    });
}

/**
 * A checkbox with data-toggles="x" shows or hides the element marked data-toggled="x".
 */
function initToggles() {
    document.querySelectorAll('[data-toggles]').forEach((checkbox) => {
        const target = document.querySelector(`[data-toggled="${checkbox.dataset.toggles}"]`);

        checkbox.addEventListener('change', () => {
            target.hidden = !checkbox.checked;
        });
    });
}

initMobileMenu();
initToggles();
initHeroSliders();
initMoodShowcases();
initCarousels();
initGalleries();
initCart();
