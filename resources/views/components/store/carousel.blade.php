@props(['label'])

{{-- A snapping row with previous/next arrows on larger screens; resources/js/carousel.js moves it. --}}
<div class="relative" data-carousel>
    <div {{ $attributes->class('snap-row') }} data-carousel-track tabindex="0" aria-label="{{ $label }}">
        {{ $slot }}
    </div>

    <button type="button" class="absolute top-1/3 -left-3 hidden size-10 place-items-center rounded-full bg-white/90 shadow ring-1 ring-ink/5 hover:bg-white md:grid" data-carousel-prev aria-label="Anterior">
        <x-store.icon name="chevron-left" class="size-5" />
    </button>
    <button type="button" class="absolute top-1/3 -right-3 hidden size-10 place-items-center rounded-full bg-white/90 shadow ring-1 ring-ink/5 hover:bg-white md:grid" data-carousel-next aria-label="Siguiente">
        <x-store.icon name="chevron-right" class="size-5" />
    </button>
</div>
