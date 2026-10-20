const SWIPE_DISTANCE = 50;

/**
 * The home hero slider. The active button's progress bar is a CSS animation lasting
 * --hero-duration; when it ends the next slide shows, so the bar and the timer never drift.
 * Hovering, keyboard focus, a hidden tab or reduced motion pause it.
 */
export function initHeroSliders() {
    document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
        const viewport = slider.querySelector('[data-hero-viewport]');
        const slides = [...slider.querySelectorAll('[data-hero-slide]')];
        const buttons = [...slider.querySelectorAll('[data-hero-go]')];
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        let current = 0;
        let isHovered = false;
        let isFocused = false;

        const updatePaused = () => {
            const isPaused = isHovered || isFocused || document.hidden || reducedMotion.matches;

            slider.toggleAttribute('data-paused', isPaused);
            viewport.setAttribute('aria-live', isPaused ? 'polite' : 'off');
        };

        const show = (index) => {
            const next = (index + slides.length) % slides.length;

            if (next === current) {
                return;
            }

            slides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === next;

                slide.toggleAttribute('data-active', isActive);
                slide.toggleAttribute('inert', !isActive);
                slide.setAttribute('aria-hidden', String(!isActive));
            });

            buttons.forEach((button, buttonIndex) => {
                if (buttonIndex === next) {
                    button.setAttribute('aria-current', 'true');
                } else {
                    button.removeAttribute('aria-current');
                }
            });

            current = next;
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => show(Number(button.dataset.heroGo)));
            button.querySelector('.hero-progress').addEventListener('animationend', () => show(current + 1));
        });

        slider.querySelector('[data-hero-prev]')?.addEventListener('click', () => show(current - 1));
        slider.querySelector('[data-hero-next]')?.addEventListener('click', () => show(current + 1));

        slider.addEventListener('pointerenter', (event) => {
            isHovered = event.pointerType === 'mouse';
            updatePaused();
        });
        slider.addEventListener('pointerleave', () => {
            isHovered = false;
            updatePaused();
        });
        slider.addEventListener('focusin', (event) => {
            isFocused = event.target.matches(':focus-visible');
            updatePaused();
        });
        slider.addEventListener('focusout', (event) => {
            isFocused = slider.contains(event.relatedTarget) && event.relatedTarget.matches(':focus-visible');
            updatePaused();
        });
        document.addEventListener('visibilitychange', updatePaused);
        reducedMotion.addEventListener('change', updatePaused);

        let swipeStartX = null;
        let didSwipe = false;

        viewport.addEventListener('pointerdown', (event) => {
            swipeStartX = event.clientX;
            didSwipe = false;
        });
        viewport.addEventListener('pointerup', (event) => {
            if (swipeStartX === null) {
                return;
            }

            const distance = event.clientX - swipeStartX;
            swipeStartX = null;

            if (Math.abs(distance) >= SWIPE_DISTANCE) {
                didSwipe = true;
                show(current + (distance < 0 ? 1 : -1));
            }
        });
        viewport.addEventListener('pointercancel', () => {
            swipeStartX = null;
        });
        viewport.addEventListener('click', (event) => {
            if (didSwipe) {
                event.preventDefault();
                didSwipe = false;
            }
        }, true);

        slider.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                show(current - 1);
            } else if (event.key === 'ArrowRight') {
                show(current + 1);
            }
        });

        updatePaused();
    });
}
