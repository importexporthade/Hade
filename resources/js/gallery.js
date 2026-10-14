/**
 * Product photo gallery: a thumbnail swaps the big photo and takes the selection ring.
 */
export function initGalleries() {
    document.querySelectorAll('[data-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-gallery-main]');
        const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                main.src = thumb.dataset.galleryThumb;

                thumbs.forEach((other) => {
                    other.classList.toggle('ring-ink', other === thumb);
                    other.classList.toggle('ring-transparent', other !== thumb);
                });
            });
        });
    });
}
