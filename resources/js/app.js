import { initCarousels } from './carousel';
import { initCart } from './cart';
import { initGalleries } from './gallery';
import { initHeroSliders } from './hero-slider';
import { initMoodShowcases } from './mood-showcase';

function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');

    toggle?.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('flex');

        menu.classList.toggle('hidden', !isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
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
