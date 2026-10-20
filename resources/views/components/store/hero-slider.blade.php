@props(['banners', 'duration' => 6500])

{{--
    The home hero: the banners from the manager as full-width slides that crossfade. Each
    navigation button fills up while its slide shows, and resources/js/hero-slider.js moves on
    when the fill completes. With a single banner it is a still image without controls.
--}}
@php
    use App\Enums\ImageKind;
    use App\Support\ImageOptimizer;

    $hasControls = $banners->count() > 1;
@endphp

<section
    class="relative isolate overflow-hidden bg-ink text-white"
    aria-roledescription="carrusel"
    aria-label="Destacados"
    style="--hero-duration: {{ $duration }}ms"
    @if ($hasControls) data-hero-slider @endif
>
    <h1 class="sr-only">Uñas con estilo para cada momento</h1>

    <div class="relative h-[clamp(30rem,74svh,42rem)] touch-pan-y" data-hero-viewport aria-live="off">
        @foreach ($banners as $banner)
            @php($bannerUrl = $banner->linkUrl())
            @php($bannerTag = $bannerUrl ? 'a' : 'div')
            @php($hasCopy = $banner->title || $banner->subtitle || $banner->button_label)
            <div
                class="hero-slide absolute inset-0"
                role="group"
                aria-roledescription="diapositiva"
                aria-label="{{ $loop->iteration }} de {{ $loop->count }}"
                data-hero-slide
                @if ($loop->first) data-active @else inert aria-hidden="true" @endif
            >
                <{{ $bannerTag }}
                    @if ($bannerUrl) href="{{ $bannerUrl }}" @endif
                    @if ($bannerUrl && $banner->link_type->opensOutsideTheStore()) target="_blank" rel="noopener" @endif
                    class="group relative block size-full"
                    draggable="false"
                >
                    <picture>
                        @if ($banner->mobile_image)
                            <source
                                media="(max-width: 640px)"
                                srcset="{{ ImageOptimizer::srcset($banner->mobile_image, ImageKind::MobileBanner) ?? ImageOptimizer::url($banner->mobile_image) }}"
                                sizes="100vw"
                            >
                        @endif
                        <x-store.image
                            :path="$banner->image"
                            :kind="ImageKind::Banner"
                            :width="1080"
                            sizes="100vw"
                            :alt="(string) $banner->title"
                            :loading="$loop->first ? 'eager' : 'lazy'"
                            :fetchpriority="$loop->first ? 'high' : 'auto'"
                            draggable="false"
                            class="hero-slide-image absolute inset-0 size-full object-cover"
                        />
                    </picture>

                    <div aria-hidden="true" @class([
                        'absolute inset-0',
                        'bg-linear-to-t from-ink/75 via-ink/25 to-transparent lg:bg-linear-to-r lg:from-ink/65 lg:via-ink/20' => $hasCopy,
                        'bg-linear-to-t from-ink/45 via-transparent to-transparent' => ! $hasCopy,
                    ])></div>

                    @if ($hasCopy)
                        <div class="store-container relative flex h-full flex-col justify-end pb-24 sm:pb-28 lg:justify-center lg:pb-10">
                            <div class="hero-slide-copy flex max-w-xl flex-col items-start gap-3 lg:max-w-2xl">
                                @if ($banner->title)
                                    <h2 class="text-balance font-serif text-[clamp(2.1rem,1.4rem+3.6vw,4.5rem)] leading-[0.98] font-medium tracking-[-0.035em]">{{ $banner->title }}</h2>
                                @endif
                                @if ($banner->subtitle)
                                    <p class="max-w-lg text-sm leading-relaxed text-white/85 sm:text-base">{{ $banner->subtitle }}</p>
                                @endif
                                @if ($banner->button_label)
                                    <span class="mt-2 inline-flex min-h-11 items-center gap-2.5 rounded-full bg-white px-6 text-sm font-semibold text-emerald shadow-[0_10px_24px_rgba(0,0,0,0.18)] transition group-hover:-translate-y-0.5">
                                        {{ $banner->button_label }}
                                        <span aria-hidden="true" class="text-lg leading-none transition-transform group-hover:translate-x-1">→</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </{{ $bannerTag }}>
            </div>
        @endforeach
    </div>

    @if ($hasControls)
        <div class="absolute inset-x-0 bottom-0 z-10">
            <div class="store-container flex items-end gap-4 pb-5 sm:gap-6 sm:pb-7">
                <ol class="grid flex-1 auto-cols-fr grid-flow-col gap-2 sm:gap-4">
                    @foreach ($banners as $banner)
                        <li class="min-w-0">
                            <button
                                type="button"
                                class="group flex w-full flex-col gap-2 py-2 text-left text-white/65 transition hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white aria-[current=true]:text-white"
                                data-hero-go="{{ $loop->index }}"
                                aria-label="Ir a la diapositiva {{ $loop->iteration }}{{ $banner->title ? ': '.$banner->title : '' }}"
                                @if ($loop->first) aria-current="true" @endif
                            >
                                <span class="block h-0.5 overflow-hidden rounded-full bg-white/30">
                                    <span class="hero-progress block h-full origin-left rounded-full bg-white"></span>
                                </span>
                                <span class="hidden items-baseline gap-2 text-xs font-medium sm:flex">
                                    <span class="tabular-nums">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if ($banner->title)
                                        <span class="truncate">{{ $banner->title }}</span>
                                    @endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <div class="hidden shrink-0 gap-2 sm:flex">
                    <button type="button" class="grid size-10 place-items-center rounded-full border border-white/35 bg-ink/20 backdrop-blur-sm transition hover:bg-white hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" data-hero-prev aria-label="Diapositiva anterior">
                        <x-store.icon name="chevron-left" class="size-5" />
                    </button>
                    <button type="button" class="grid size-10 place-items-center rounded-full border border-white/35 bg-ink/20 backdrop-blur-sm transition hover:bg-white hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" data-hero-next aria-label="Diapositiva siguiente">
                        <x-store.icon name="chevron-right" class="size-5" />
                    </button>
                </div>
            </div>
        </div>
    @endif
</section>
