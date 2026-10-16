@php
    use App\Enums\HomeSectionType;
    use App\Enums\ImageKind;
    use App\Support\ImageOptimizer;
@endphp

<x-store-layout>
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
