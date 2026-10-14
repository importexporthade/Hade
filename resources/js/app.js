import { initCarousels } from './carousel';
import { initCart } from './cart';
import { initGalleries } from './gallery';

function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');

    toggle?.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('flex');

        menu.classList.toggle('hidden', !isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
    });
}

initMobileMenu();
initCarousels();
initGalleries();
initCart();
