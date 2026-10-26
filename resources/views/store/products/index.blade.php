<x-store-layout :title="$search !== '' ? 'Buscar: '.$search : ($isLatest ? 'Novedades' : 'Productos')">
    <section class="bg-cream pt-5 sm:pt-7" aria-labelledby="products-title">
        <div class="store-container">
            <div class="relative isolate min-h-56 overflow-hidden rounded-[2rem] bg-blush shadow-[0_18px_45px_-32px_rgba(23,23,23,0.4)] sm:min-h-64">
                <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -right-8 -bottom-10 h-[85%] w-auto opacity-60 select-none sm:-right-4 sm:-bottom-16 sm:h-[135%] sm:opacity-100">

                <div class="relative z-10 flex min-h-56 max-w-2xl flex-col justify-center px-5 py-8 sm:min-h-64 sm:px-9 sm:py-10 lg:px-12">
                    <nav class="text-[0.68rem] text-burgundy/65" aria-label="Ruta">
                        <a href="{{ route('home') }}" class="transition-colors hover:text-burgundy">Inicio</a>
                        <span class="mx-1.5" aria-hidden="true">/</span>
                        <span aria-current="page">{{ $isLatest ? 'Novedades' : 'Productos' }}</span>
                    </nav>

                    <p class="mt-5 text-[0.55rem] font-semibold tracking-[0.24em] text-burgundy/70 uppercase">{{ $search !== '' ? 'Resultados de búsqueda' : ($isLatest ? 'Recién llegados' : 'Catálogo Hade') }}</p>
                    <h1 id="products-title" class="mt-1 max-w-[16ch] text-balance font-serif text-[clamp(2.15rem,1.6rem+2.3vw,3.5rem)] leading-[0.98] font-medium tracking-[-0.04em] text-burgundy">
                        {{ $search !== '' ? 'Resultados para “'.$search.'”' : ($isLatest ? 'Novedades' : 'Todos los productos') }}
                    </h1>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-ink/65 sm:text-base">
                        {{ $search !== '' ? 'Explora los diseños que coinciden con tu búsqueda.' : ($isLatest ? 'Descubre los diseños más recientes que acaban de llegar a Hade.' : 'Descubre todos nuestros diseños y encuentra el estilo perfecto para ti.') }}
                    </p>

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
                    <div class="py-16 text-center">
                        <p class="font-serif text-xl">{{ $search !== '' ? 'No encontramos productos con ese nombre.' : 'Aún no hay productos disponibles.' }}</p>
                        <a href="{{ route('products.index') }}" class="group mt-6 inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-burgundy px-6 text-sm font-semibold text-cream shadow-[0_10px_24px_-14px_rgba(106,30,46,0.65)] transition duration-300 hover:-translate-y-0.5 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy">
                            Ver todos los productos
                            <x-store.icon name="chevron-right" class="size-3.5 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" />
                        </a>
                    </div>
                </x-slot:empty>
            </x-store.product-grid>
        </div>
    </div>
</x-store-layout>
