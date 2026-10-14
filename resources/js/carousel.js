/**
 * Previous/next arrows for the snapping product rows; arrows hide when the row fits.
 */
export function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');

        const update = () => {
            const maxScroll = track.scrollWidth - track.clientWidth - 1;

            previous.style.visibility = track.scrollLeft > 0 ? 'visible' : 'hidden';
            next.style.visibility = track.scrollLeft < maxScroll ? 'visible' : 'hidden';
        };

        previous.addEventListener('click', () => track.scrollBy({ left: -track.clientWidth * 0.9 }));
        next.addEventListener('click', () => track.scrollBy({ left: track.clientWidth * 0.9 }));
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });
}
