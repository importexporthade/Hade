/** Switches the manicure preview while keeping every mood image in the same stage. */
export function initMoodShowcases() {
    document.querySelectorAll('[data-mood-showcase]').forEach((showcase) => {
        const options = [...showcase.querySelectorAll('[data-mood-option]')];
        const images = [...showcase.querySelectorAll('[data-mood-image]')];
        const status = showcase.querySelector('[data-mood-status]');

        options.forEach((option) => {
            option.addEventListener('click', () => {
                const selectedMood = option.dataset.moodOption;

                options.forEach((otherOption) => {
                    otherOption.setAttribute('aria-pressed', String(otherOption === option));
                });

                images.forEach((image) => {
                    const isSelected = image.dataset.moodImage === selectedMood;

                    image.toggleAttribute('data-active', isSelected);
                    image.setAttribute('aria-hidden', String(! isSelected));
                });

                if (status) {
                    status.textContent = `Estilo ${option.textContent.trim()} seleccionado.`;
                }
            });
        });
    });
}
