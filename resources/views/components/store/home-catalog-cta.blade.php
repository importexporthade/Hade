<section class="relative isolate flex min-h-[19.44rem] items-center overflow-hidden text-center text-white sm:min-h-[16.2rem] lg:min-h-[19.44rem]" aria-labelledby="catalogo-cta">
    <img src="{{ asset('images/hero-nail-stickers.webp') }}" srcset="{{ asset('images/hero-nail-stickers-768.webp') }} 768w, {{ asset('images/hero-nail-stickers.webp') }} 1536w" sizes="100vw" alt="" loading="lazy" class="absolute inset-0 -z-20 size-full object-cover object-center">
    <span class="absolute inset-0 -z-10 bg-[#6f5e50]/20" aria-hidden="true"></span>
    <span class="absolute inset-0 -z-10 bg-linear-to-b from-[#b9a28f]/55 via-[#8b7360]/65 to-[#514840]/75" aria-hidden="true"></span>

    <div class="store-container py-[2.7rem] sm:py-[3.15rem]">
        <div class="mx-auto flex max-w-3xl flex-col items-center gap-3.5">
            <p class="text-[0.62rem] font-semibold tracking-[0.24em] text-white/85 uppercase">Lista para encontrar tu próximo favorito</p>
            <h2 id="catalogo-cta" class="text-balance font-serif text-[clamp(2.25rem,1.6rem+2.4vw,3.5rem)] leading-tight font-medium tracking-[-0.03em]">Pequeños detalles, grandes expresiones.</h2>
            <p class="text-balance text-sm leading-relaxed text-white/80 sm:text-base md:whitespace-nowrap">Descubre el universo Hade y encuentra diseños, kits y esenciales hechos para ti.</p>
            <div class="mt-3 flex flex-wrap justify-center gap-3">
                <a href="{{ route('products.index') }}" class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-white px-7 text-sm font-semibold text-[#8b7360] transition duration-300 hover:bg-[#f2e8da] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                    Entrar a la tienda
                    <x-store.icon name="chevron-right" class="size-4 transition-transform duration-300 group-hover:translate-x-0.5" />
                </a>
                <a href="{{ route('products.index', ['collection' => 'latest']) }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/70 px-7 text-sm font-semibold text-white transition duration-300 hover:border-white hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                    Ver novedades
                </a>
            </div>
        </div>
    </div>
</section>
