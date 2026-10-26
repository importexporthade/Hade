@props(['label', 'autoplay' => false, 'interval' => 5000, 'mergeLastPage' => false, 'arrows' => true])

{{-- A snapping row with previous/next arrows on larger screens; resources/js/carousel.js moves it. --}}
<div
    class="relative"
    data-carousel
    @if ($autoplay) data-carousel-autoplay data-carousel-interval="{{ $interval }}" @endif
    @if ($mergeLastPage) data-carousel-merge-last-page @endif
>
    <div {{ $attributes->class('snap-row') }} data-carousel-track tabindex="0" aria-label="{{ $label }}">
        {{ $slot }}
    </div>

    <button type="button" @class(['absolute top-1/3 -left-3 size-10 place-items-center rounded-full bg-white/90 shadow ring-1 ring-ink/5 transition hover:scale-105 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald', 'hidden' => ! $arrows, 'grid' => $arrows && $autoplay, 'hidden md:grid' => $arrows && ! $autoplay]) data-carousel-prev aria-label="Anterior">
        <x-store.icon name="chevron-left" class="size-5" />
    </button>
    <button type="button" @class(['absolute top-1/3 -right-3 size-10 place-items-center rounded-full bg-white/90 shadow ring-1 ring-ink/5 transition hover:scale-105 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald', 'hidden' => ! $arrows, 'grid' => $arrows && $autoplay, 'hidden md:grid' => $arrows && ! $autoplay]) data-carousel-next aria-label="Siguiente">
        <x-store.icon name="chevron-right" class="size-5" />
    </button>

    @if ($autoplay)
        <div class="mt-4 flex justify-center gap-2" data-carousel-pagination role="group" aria-label="Paginación de {{ $label }}"></div>
    @endif
</div>
