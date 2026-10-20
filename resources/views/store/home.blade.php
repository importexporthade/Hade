@php
    use App\Enums\HomeSectionType;
    use App\Enums\ImageKind;
    use App\Support\ImageOptimizer;
@endphp

<x-store-layout>
    <section class="relative isolate overflow-hidden bg-[#f1ece3]" aria-labelledby="hero-title">
        <div class="store-container grid lg:min-h-[38rem] lg:grid-cols-[0.9fr_1.1fr] lg:px-0 xl:max-w-[90rem]">
            <div class="relative z-10 flex flex-col justify-center px-1 py-12 sm:px-6 sm:py-16 lg:px-12 lg:py-20 xl:px-16">
                <p class="flex items-center gap-3 text-[0.68rem] font-semibold tracking-[0.32em] text-emerald uppercase sm:text-xs">
                    <span>Fáciles</span>
                    <span aria-hidden="true" class="size-1 rounded-full bg-sage-dark"></span>
                    <span>Elegantes</span>
                    <span aria-hidden="true" class="size-1 rounded-full bg-sage-dark"></span>
                    <span>Tuyas</span>
                </p>

                <h1 id="hero-title" class="mt-5 max-w-3xl font-serif text-[clamp(3.25rem,7vw,6.5rem)] leading-[0.92] font-medium tracking-[-0.045em] text-[#174f3d]">
                    Uñas con estilo para cada momento
                </h1>

                <p class="mt-6 max-w-xl text-base leading-relaxed text-ink/70 sm:text-lg">
                    Diseños que se aplican en minutos, duran por días y hacen que cada look se sienta completamente tuyo.
                </p>

                <div class="mt-8">
                    <a href="{{ route('products.index') }}" class="group inline-flex min-h-14 items-center justify-center gap-3 rounded-full bg-emerald px-8 text-sm font-semibold text-white shadow-[0_12px_30px_rgba(31,92,69,0.18)] transition hover:-translate-y-0.5 hover:bg-[#174f3d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald">
                        Descubre tus stickers
                        <span aria-hidden="true" class="text-xl leading-none transition-transform group-hover:translate-x-1">→</span>
                    </a>
                </div>

                <ul class="mt-10 grid max-w-2xl gap-4 border-t border-emerald/15 pt-6 text-sm text-ink/70 sm:grid-cols-3 lg:mt-14">
                    @foreach (['Aplicación sencilla', 'Hasta 14 días', 'Diseños únicos'] as $benefit)
                        <li class="flex items-center gap-2.5">
                            <span class="grid size-7 shrink-0 place-items-center rounded-full border border-emerald/20 bg-white/60 text-emerald">
                                <x-store.icon name="check" class="size-4" />
                            </span>
                            <span>{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative min-h-[28rem] overflow-hidden sm:min-h-[34rem] lg:min-h-full">
                <img
                    src="{{ asset('images/hero-nail-stickers.jpg') }}"
                    alt="Manicure en tonos nude con flores blancas y detalles verdes"
                    width="1536"
                    height="1024"
                    fetchpriority="high"
                    class="absolute inset-0 size-full object-cover object-center"
                >
                <div aria-hidden="true" class="absolute inset-y-0 left-0 hidden w-24 bg-linear-to-r from-[#f1ece3] to-transparent lg:block"></div>

                <div class="absolute right-5 bottom-5 grid size-28 place-items-center rounded-full border border-white/35 bg-emerald/95 p-4 text-center text-[0.62rem] leading-relaxed font-semibold tracking-[0.2em] text-white uppercase shadow-xl backdrop-blur-sm sm:right-8 sm:bottom-8 sm:size-32">
                    Tu estilo<br>en minutos
                </div>
            </div>
        </div>
    </section>

    @if ($banners->isNotEmpty())
        <section aria-label="Promociones" class="bg-white">
            <x-store.carousel label="Promociones" class="gap-0">
                @foreach ($banners as $banner)
                    @php($bannerUrl = $banner->linkUrl())
                    @php($bannerTag = $bannerUrl ? 'a' : 'div')
                    <{{ $bannerTag }}
                        @if ($bannerUrl) href="{{ $bannerUrl }}" @endif
                        @if ($bannerUrl && $banner->link_type->opensOutsideTheStore()) target="_blank" rel="noopener" @endif
                        class="relative block w-full shrink-0"
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
                                class="max-h-[32rem] w-full object-cover"
                            />
                        </picture>

                        @if ($banner->title || $banner->button_label)
                            <div class="absolute inset-0 flex items-end bg-linear-to-t from-ink/50 via-transparent to-transparent">
                                <div class="store-container pb-8 text-white sm:pb-12">
                                    @if ($banner->title)
                                        <p class="max-w-xl font-serif text-3xl sm:text-5xl">{{ $banner->title }}</p>
                                    @endif
                                    @if ($banner->subtitle)
                                        <p class="mt-2 max-w-xl text-sm sm:text-base">{{ $banner->subtitle }}</p>
                                    @endif
                                    @if ($banner->button_label)
                                        <span class="btn-sage mt-5">{{ $banner->button_label }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </{{ $bannerTag }}>
                @endforeach
            </x-store.carousel>
        </section>
    @endif

    @forelse ($sections as $section)
        @switch($section->type)
            @case(HomeSectionType::Offers)
                @php($products = $section->products())
                @if ($products->isNotEmpty())
                    <section class="pt-8 sm:pt-10" aria-labelledby="seccion-{{ $section->id }}">
                        <div class="store-container">
                            <h2 id="seccion-{{ $section->id }}" class="sr-only">{{ $section->title }}</h2>
                            <x-store.carousel :label="$section->title">
                                @foreach ($products as $product)
                                    <x-store.product-card :product="$product" size="large" class="w-[80%] shrink-0 sm:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.667rem)]" />
                                @endforeach
                            </x-store.carousel>
                        </div>
                    </section>
                @endif
                @break

            @case(HomeSectionType::Categories)
                @if ($categories->isNotEmpty())
                    <section class="pt-12" aria-labelledby="seccion-{{ $section->id }}">
                        <div class="store-container">
                            <h2 id="seccion-{{ $section->id }}" class="section-heading">{{ $section->title }}</h2>
                            @if ($section->subtitle)
                                <p class="mt-1 text-sm text-ink/60">{{ $section->subtitle }}</p>
                            @endif
                            <ul class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('categories.show', $category) }}" class="group block">
                                            <span class="block aspect-square overflow-hidden bg-tile">
                                                @if ($category->image)
                                                    <x-store.image
                                                        :path="$category->image"
                                                        :kind="ImageKind::Category"
                                                        :width="320"
                                                        sizes="(min-width: 1024px) 240px, (min-width: 640px) 33vw, 50vw"
                                                        loading="lazy"
                                                        class="size-full object-cover transition duration-500 group-hover:scale-105"
                                                    />
                                                @else
                                                    <span class="grid size-full place-items-center bg-sage-light font-serif text-4xl text-emerald">{{ mb_substr($category->name, 0, 1) }}</span>
                                                @endif
                                            </span>
                                            <span class="mt-2 block text-sm font-medium">{{ $category->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endif
                @break

            @default
                @php($products = $section->products())
                @if ($products->isNotEmpty())
                    <section class="pt-12" aria-labelledby="seccion-{{ $section->id }}">
                        <div class="store-container">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <h2 id="seccion-{{ $section->id }}" @class(['section-heading', 'font-serif font-semibold' => $section->type === HomeSectionType::Latest])>{{ $section->title }}</h2>
                                    @if ($section->subtitle)
                                        <p class="mt-1 text-sm text-ink/60">{{ $section->subtitle }}</p>
                                    @endif
                                </div>
                                <a href="{{ $section->category ? route('categories.show', $section->category) : route('products.index') }}" class="shrink-0 text-sm text-ink/70 hover:text-ink">Ver más</a>
                            </div>
                            <x-store.carousel :label="$section->title" class="mt-5">
                                @foreach ($products as $product)
                                    <x-store.product-card :product="$product" class="w-40 shrink-0 sm:w-48 lg:w-[calc(20%-0.8rem)]" />
                                @endforeach
                            </x-store.carousel>
                        </div>
                    </section>
                @endif
        @endswitch
    @empty
        <section class="store-container py-24 text-center">
            <p class="font-serif text-2xl">Muy pronto</p>
            <p class="mt-2 text-ink/60">Estamos preparando la tienda.</p>
        </section>
    @endforelse

    <div class="store-container pt-12 text-center">
        <a href="{{ route('products.index') }}" class="btn-outline">Ver todos los productos</a>
    </div>
</x-store-layout>
