import { initCarousels } from './carousel';
import { initCart } from './cart';
import { initGalleries } from './gallery';
import { initHeroSliders } from './hero-slider';
import { initMoodShowcases } from './mood-showcase';

function initMobileMenu() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');
    const panel = document.querySelector('[data-menu-panel]');
    const label = toggle?.querySelector('[data-menu-label]');
    const searchTrigger = document.querySelector('[data-mobile-search-open]');
    const searchInput = document.querySelector('#buscar-movil');
    let returnFocus = toggle;

    if (!toggle || !menu || !panel) {
        return;
    }

    const isOpen = () => panel.dataset.open === 'true';

    const setOpen = (open) => {
        panel.dataset.open = String(open);
        panel.setAttribute('aria-hidden', String(!open));
        panel.inert = !open;
        toggle.setAttribute('aria-expanded', String(open));
        searchTrigger?.setAttribute('aria-expanded', String(open));

        if (label) {
            label.textContent = open ? 'Cerrar menú' : 'Abrir menú';
        }
    };

    toggle.addEventListener('click', () => {
        returnFocus = toggle;
        setOpen(!isOpen());
    });

    searchTrigger?.addEventListener('click', () => {
        returnFocus = searchTrigger;
        setOpen(true);
        searchInput?.focus({ preventScroll: true });
    });

    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) {
            setOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (!isOpen() || toggle.contains(event.target) || searchTrigger?.contains(event.target) || panel.contains(event.target)) {
            return;
        }

        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !isOpen()) {
            return;
        }

        setOpen(false);
        returnFocus.focus();
    });

    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches) {
            setOpen(false);
        }
    });
}

function initDesktopDropdowns() {
    const dropdowns = [...document.querySelectorAll('[data-nav-dropdown]')];
    const desktop = window.matchMedia('(min-width: 64rem)');

    const setOpen = (dropdown, open) => {
        const trigger = dropdown.querySelector('[data-nav-trigger]');
        const panel = dropdown.querySelector('[data-nav-panel]');

        dropdown.dataset.open = String(open);
        panel.dataset.open = String(open);
        trigger.setAttribute('aria-expanded', String(open));
    };

    const closeOthers = (current) => {
        dropdowns.forEach((dropdown) => {
            if (dropdown !== current) {
                setOpen(dropdown, false);
            }
        });
    };

    const closeTimers = new Map();

    const cancelClose = (dropdown) => {
        clearTimeout(closeTimers.get(dropdown));
        closeTimers.delete(dropdown);
    };

    dropdowns.forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-nav-trigger]');

        trigger.addEventListener('click', () => {
            const isHovered = desktop.matches && dropdown.matches(':hover');
            const willOpen = isHovered || dropdown.dataset.open !== 'true';

            cancelClose(dropdown);
            closeOthers(dropdown);
            setOpen(dropdown, willOpen);
        });

        dropdown.addEventListener('mouseenter', () => {
            if (desktop.matches) {
                cancelClose(dropdown);
                closeOthers(dropdown);
                setOpen(dropdown, true);
            }
        });

        dropdown.addEventListener('mouseleave', () => {
            if (desktop.matches) {
                cancelClose(dropdown);
                closeTimers.set(dropdown, setTimeout(() => setOpen(dropdown, false), 200));
            }
        });

        dropdown.addEventListener('focusin', () => {
            closeOthers(dropdown);
            setOpen(dropdown, true);
        });

        dropdown.addEventListener('focusout', () => {
            queueMicrotask(() => {
                if (!dropdown.contains(document.activeElement)) {
                    setOpen(dropdown, false);
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                setOpen(dropdown, false);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        dropdowns.forEach((dropdown) => {
            if (dropdown.dataset.open === 'true') {
                setOpen(dropdown, false);
                dropdown.querySelector('[data-nav-trigger]').focus();
            }
        });
    });
}

function initMobileSubmenus() {
    const submenus = [...document.querySelectorAll('[data-mobile-submenu]')];

    const setOpen = (submenu, open) => {
        const trigger = submenu.querySelector('[data-submenu-trigger]');
        const panel = submenu.querySelector('[data-submenu-panel]');

        submenu.dataset.open = String(open);
        panel.dataset.open = String(open);
        panel.setAttribute('aria-hidden', String(!open));
        panel.inert = !open;
        trigger.setAttribute('aria-expanded', String(open));
    };

    submenus.forEach((submenu) => {
        const trigger = submenu.querySelector('[data-submenu-trigger]');

        setOpen(submenu, trigger.getAttribute('aria-expanded') === 'true');

        trigger.addEventListener('click', () => {
            const willOpen = submenu.dataset.open !== 'true';

            submenus.forEach((otherSubmenu) => {
                if (otherSubmenu !== submenu) {
                    setOpen(otherSubmenu, false);
                }
            });

            setOpen(submenu, willOpen);
        });
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
initDesktopDropdowns();
initMobileSubmenus();
initToggles();
initHeroSliders();
initMoodShowcases();
initCarousels();
initGalleries();
initCart();
