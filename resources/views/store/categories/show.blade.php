@php
    use App\Enums\ImageKind;
@endphp

<x-store-layout :title="$category->name" :description="$category->description" :image="$category->imageUrl()">
    <section class="bg-cream pt-5 sm:pt-7" aria-labelledby="category-title">
        <div class="store-container">
            <div class="relative isolate min-h-56 overflow-hidden rounded-[2rem] bg-blush shadow-[0_18px_45px_-32px_rgba(23,23,23,0.4)] sm:min-h-64">
                @if ($category->image)
                    <div class="absolute inset-0 sm:left-auto sm:w-[48%]">
                        <x-store.image
                            :path="$category->image"
                            :kind="ImageKind::Category"
                            :width="960"
                            sizes="(min-width: 640px) 48vw, 100vw"
                            :alt="''"
                            class="size-full object-cover opacity-45 sm:opacity-100"
                        />
                    </div>
                    <div aria-hidden="true" class="absolute inset-0 bg-linear-to-r from-blush via-blush/95 to-blush/55 sm:via-blush/95 sm:to-transparent"></div>
                @else
                    <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -right-8 -bottom-10 h-[85%] w-auto opacity-60 select-none sm:-right-4 sm:-bottom-16 sm:h-[135%] sm:opacity-100">
                @endif

                <div class="relative z-10 flex min-h-56 max-w-2xl flex-col justify-center px-5 py-8 sm:min-h-64 sm:px-9 sm:py-10 lg:px-12">
                    <nav class="text-[0.68rem] text-burgundy/65" aria-label="Ruta">
                        <a href="{{ route('home') }}" class="transition-colors hover:text-burgundy">Inicio</a>
                        <span class="mx-1.5" aria-hidden="true">/</span>
                        <span aria-current="page">{{ $category->name }}</span>
                    </nav>

                    <p class="mt-5 text-[0.55rem] font-semibold tracking-[0.24em] text-burgundy/70 uppercase">Colección Hade</p>
                    <h1 id="category-title" class="mt-1 max-w-[14ch] text-balance font-serif text-[clamp(2.15rem,1.6rem+2.3vw,3.5rem)] leading-[0.98] font-medium tracking-[-0.04em] text-burgundy">{{ $category->name }}</h1>
                    @if ($category->description)
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-ink/65 sm:text-base">{{ $category->description }}</p>
                    @endif

                    <p class="mt-5 inline-flex w-fit items-center rounded-full bg-cream/75 px-3 py-1.5 text-xs font-semibold text-burgundy backdrop-blur-sm">
                        {{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="store-container pt-8 sm:pt-10">
        <div>
            <x-store.product-grid :products="$products" card-size="latest">
                <x-slot:empty>
                    <p class="py-16 text-center text-ink/60">Pronto tendremos productos en esta categoría.</p>
                </x-slot:empty>
            </x-store.product-grid>
        </div>
    </div>
</x-store-layout>
