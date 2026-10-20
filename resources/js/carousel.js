/** Previous/next controls, with optional looping autoplay for promoted rows. */
export function initCarousels() {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-carousel-track]');
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');
        const pagination = carousel.querySelector('[data-carousel-pagination]');
        const autoplay = carousel.hasAttribute('data-carousel-autoplay');
        const mergeLastPage = carousel.hasAttribute('data-carousel-merge-last-page');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const interval = Number(carousel.dataset.carouselInterval) || 5000;
        const pausedReasons = new Set();
        let scrollPositions = [];
        let paginationDots = [];
        let activePosition = -1;
        let timeout;
        let remaining = interval;
        let startedAt;
        let wasOverflowing = false;

        const maxScroll = () => Math.max(0, track.scrollWidth - track.clientWidth);
        const isOverflowing = () => maxScroll() > 1;
        const scrollStep = () => {
            const firstItem = track.firstElementChild;
            const gap = Number.parseFloat(window.getComputedStyle(track).columnGap) || 0;

            return firstItem ? firstItem.getBoundingClientRect().width + gap : track.clientWidth * 0.9;
        };

        const restartProgress = () => {
            const progress = pagination?.querySelector('[aria-current="true"] .carousel-dot-progress');

            if (!progress) {
                return;
            }

            progress.style.animation = 'none';
            progress.getBoundingClientRect();
            progress.style.animation = '';
        };

        const getScrollPositions = () => {
            const limit = maxScroll();
            const step = scrollStep();
            const positions = [0];

            for (let left = step; left < limit - 1; left += step) {
                positions.push(left);
            }

            if (limit > 1 && positions.at(-1) < limit - 1) {
                positions.push(limit);
            }

            if (mergeLastPage && positions.length > 2) {
                positions.splice(-2, 1);
            }

            return positions;
        };

        const closestPositionIndex = () => scrollPositions.reduce((closestIndex, position, index) => (
            Math.abs(position - track.scrollLeft) < Math.abs(scrollPositions[closestIndex] - track.scrollLeft)
                ? index
                : closestIndex
        ), 0);

        const updatePagination = () => {
            if (!pagination || paginationDots.length === 0) {
                return;
            }

            const nextPosition = closestPositionIndex();

            if (nextPosition === activePosition) {
                return;
            }

            activePosition = nextPosition;
            paginationDots.forEach((dot, index) => {
                if (index === activePosition) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
            restartProgress();
        };

        const clearAutoplay = () => {
            window.clearTimeout(timeout);
            timeout = undefined;
        };

        const move = (direction) => {
            if (scrollPositions.length === 0) {
                return;
            }

            const currentPosition = closestPositionIndex();
            const nextPosition = (currentPosition + direction + scrollPositions.length) % scrollPositions.length;

            track.scrollTo({
                left: scrollPositions[nextPosition],
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        };

        const scheduleAutoplay = () => {
            clearAutoplay();

            if (!autoplay || reducedMotion.matches || !isOverflowing() || pausedReasons.size > 0) {
                return;
            }

            startedAt = performance.now();
            timeout = window.setTimeout(() => {
                move(1);
                remaining = interval;
                restartProgress();
                scheduleAutoplay();
            }, remaining);
        };

        const resetAutoplay = () => {
            remaining = interval;
            restartProgress();
            scheduleAutoplay();
        };

        const rebuildPagination = () => {
            if (!pagination) {
                return;
            }

            const nextPositions = getScrollPositions();
            const positionsChanged = nextPositions.length !== scrollPositions.length
                || nextPositions.some((position, index) => Math.abs(position - scrollPositions[index]) > 1);

            if (!positionsChanged) {
                updatePagination();

                return;
            }

            scrollPositions = nextPositions;
            activePosition = -1;
            pagination.replaceChildren();
            paginationDots = scrollPositions.map((left, index) => {
                const dot = document.createElement('button');
                const progress = document.createElement('span');

                dot.type = 'button';
                dot.className = 'carousel-dot';
                dot.setAttribute('aria-label', `Ir a la página ${index + 1}`);
                progress.className = 'carousel-dot-progress';
                progress.style.setProperty('--carousel-duration', `${interval}ms`);
                progress.setAttribute('aria-hidden', 'true');
                dot.append(progress);
                dot.addEventListener('click', () => {
                    track.scrollTo({ left, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
                    resetAutoplay();
                });
                pagination.append(dot);

                return dot;
            });

            updatePagination();
        };

        const pause = (reason) => {
            if (pausedReasons.has(reason)) {
                return;
            }

            pausedReasons.add(reason);
            carousel.setAttribute('data-carousel-paused', '');

            if (timeout !== undefined) {
                remaining = Math.max(0, remaining - (performance.now() - startedAt));
            }

            clearAutoplay();
        };

        const resume = (reason) => {
            pausedReasons.delete(reason);

            if (pausedReasons.size === 0) {
                carousel.removeAttribute('data-carousel-paused');
                scheduleAutoplay();
            }
        };

        const update = () => {
            const overflowing = isOverflowing();

            if (pagination) {
                pagination.hidden = !overflowing;
            }

            previous.style.visibility = overflowing && track.scrollLeft > 1 ? 'visible' : 'hidden';
            next.style.visibility = overflowing && track.scrollLeft < maxScroll() - 1 ? 'visible' : 'hidden';
            rebuildPagination();
            updatePagination();

            if (autoplay && overflowing !== wasOverflowing) {
                wasOverflowing = overflowing;
                resetAutoplay();
            }
        };

        previous.addEventListener('click', () => {
            move(-1);
            resetAutoplay();
        });
        next.addEventListener('click', () => {
            move(1);
            resetAutoplay();
        });
        track.addEventListener('scroll', update, { passive: true });
        track.addEventListener('pointerdown', () => pause('drag'));
        track.addEventListener('pointerup', () => {
            resetAutoplay();
            resume('drag');
        });
        track.addEventListener('pointercancel', () => resume('drag'));
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                pause('document');
            } else {
                resume('document');
            }
        });
        reducedMotion.addEventListener('change', () => {
            update();
            resetAutoplay();
        });
        window.addEventListener('resize', update);
        update();
    });
}
