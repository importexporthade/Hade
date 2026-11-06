const ANIMATION_DURATION = 450;
const ANIMATION_EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';

/** Smoothly opens one FAQ answer at a time while preserving native details as a fallback. */
export function initFaqAccordions() {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    document.querySelectorAll('[data-faq-accordion]').forEach((accordion) => {
        const items = [...accordion.querySelectorAll('[data-faq-item]')];
        const animations = new WeakMap();

        const setOpen = (item, open) => {
            const trigger = item.querySelector('[data-faq-trigger]');
            const panel = item.querySelector('[data-faq-panel]');
            const computedStyle = item.open ? window.getComputedStyle(panel) : null;
            const currentHeight = item.open ? panel.getBoundingClientRect().height : 0;
            const currentOpacity = computedStyle ? Number.parseFloat(computedStyle.opacity) : 0;
            const currentTransform = computedStyle?.transform === 'none'
                ? 'translateY(0)'
                : computedStyle?.transform ?? 'translateY(-0.25rem)';

            animations.get(item)?.cancel();
            item.dataset.open = String(open);
            trigger.setAttribute('aria-expanded', String(open));

            if (reducedMotion.matches || typeof panel.animate !== 'function') {
                item.open = open;
                panel.style.removeProperty('overflow');
                animations.delete(item);

                return;
            }

            if (open) {
                item.open = true;
            }

            panel.style.overflow = 'hidden';

            const animation = panel.animate([
                {
                    height: `${currentHeight}px`,
                    opacity: currentOpacity,
                    transform: currentTransform,
                },
                {
                    height: open ? `${panel.scrollHeight}px` : '0px',
                    opacity: open ? 1 : 0,
                    transform: open ? 'translateY(0)' : 'translateY(-0.25rem)',
                },
            ], {
                duration: ANIMATION_DURATION,
                easing: ANIMATION_EASING,
                fill: 'both',
            });

            animations.set(item, animation);

            animation.addEventListener('finish', () => {
                if (animations.get(item) !== animation) {
                    return;
                }

                if (!open) {
                    item.open = false;
                }

                animation.cancel();
                panel.style.removeProperty('overflow');
                animations.delete(item);
            });
        };

        items.forEach((item) => {
            const trigger = item.querySelector('[data-faq-trigger]');

            item.removeAttribute('name');
            item.dataset.open = String(item.open);
            trigger.setAttribute('aria-expanded', String(item.open));

            trigger.addEventListener('click', (event) => {
                event.preventDefault();

                const willOpen = item.dataset.open !== 'true';

                if (willOpen) {
                    items.forEach((otherItem) => {
                        if (otherItem !== item && otherItem.dataset.open === 'true') {
                            setOpen(otherItem, false);
                        }
                    });
                }

                setOpen(item, willOpen);
            });
        });
    });
}
