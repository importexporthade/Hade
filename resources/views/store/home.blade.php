@php
    use App\Enums\HomeSectionType;
    use App\Enums\ImageKind;
@endphp

<x-store-layout>
    @if ($banners->isNotEmpty())
        <x-store.hero-slider :banners="$banners" />
    @else
        <section class="relative isolate overflow-hidden bg-[#f1ece3]" aria-labelledby="hero-title">
            <div class="grid min-h-[clamp(28rem,68svh,36rem)] min-w-0 lg:mx-auto lg:min-h-[32rem] lg:max-w-7xl lg:grid-cols-[0.9fr_1.1fr] xl:max-w-[90rem]">
                <div class="relative z-10 col-start-1 row-start-1 flex min-w-0 flex-col justify-end gap-4 px-5 pb-[clamp(2.5rem,8svh,5rem)] sm:px-7 lg:justify-center lg:gap-0 lg:px-10 lg:py-14 xl:px-14">
                    <p class="order-2 flex items-center gap-2 text-[0.625rem] font-semibold tracking-[0.24em] text-emerald uppercase sm:text-[0.68rem] lg:order-none lg:gap-2.5">
                        <span>Fáciles</span>
                        <span aria-hidden="true" class="size-1 rounded-full bg-sage-dark"></span>
                        <span>Elegantes</span>
                        <span aria-hidden="true" class="size-1 rounded-full bg-sage-dark"></span>
                        <span>Tuyas</span>
                    </p>

                    <h1 id="hero-title" class="order-1 max-w-[12ch] text-balance font-serif text-[clamp(2.25rem,calc(1.16rem+5.45vw),2.625rem)] leading-[0.98] font-medium tracking-[-0.045em] text-ink lg:order-none lg:mt-4 lg:max-w-2xl lg:text-[clamp(2.6rem,4.7vw,4.75rem)] lg:leading-[0.95] lg:text-[#174f3d]">
                        Uñas con estilo para cada momento
                    </h1>

                    <p class="mt-4 hidden max-w-lg text-sm leading-relaxed text-ink/70 sm:text-base lg:block">
                        Diseños que se aplican en minutos, duran por días y hacen que cada look se sienta completamente tuyo.
                    </p>

                    <div class="order-3 lg:order-none lg:mt-6">
                        <a href="{{ route('products.index') }}" class="group inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-emerald px-5 text-xs font-semibold text-white shadow-[0_10px_24px_rgba(31,92,69,0.16)] transition hover:-translate-y-0.5 hover:bg-[#174f3d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald lg:min-h-12 lg:gap-2.5 lg:px-6 lg:text-sm">
                            Descubre tus stickers
                            <span aria-hidden="true" class="text-lg leading-none transition-transform group-hover:translate-x-1">→</span>
                        </a>
                    </div>

                    <ul class="mt-8 hidden max-w-2xl gap-3 border-t border-emerald/15 pt-5 text-xs text-ink/70 sm:grid-cols-3 sm:text-sm lg:mt-10 lg:grid">
                        @foreach (['Aplicación sencilla', 'Hasta 14 días', 'Diseños únicos'] as $benefit)
                            <li class="flex items-center gap-2.5">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full border border-emerald/20 bg-white/60 text-emerald">
                                    <x-store.icon name="check" class="size-3.5" />
                                </span>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative col-start-1 row-start-1 min-h-[clamp(28rem,68svh,36rem)] min-w-0 overflow-hidden lg:col-start-2 lg:min-h-full">
                    <img
                        src="{{ asset('images/hero-nail-stickers.jpg') }}"
                        alt="Manicure en tonos nude con flores blancas y detalles verdes"
                        width="1536"
                        height="1024"
                        fetchpriority="high"
                        class="absolute inset-0 size-full object-cover object-[60%_center] sm:object-[58%_center] lg:object-center"
                    >
                    <div aria-hidden="true" class="absolute inset-y-0 left-0 w-[84%] bg-linear-to-r from-[#f1ece3]/95 via-[#f1ece3]/72 to-transparent lg:hidden"></div>
                    <div aria-hidden="true" class="absolute inset-y-0 left-0 hidden w-20 bg-linear-to-r from-[#f1ece3] to-transparent lg:block"></div>

                    <div class="absolute right-4 bottom-4 hidden size-24 place-items-center rounded-full border border-white/35 bg-emerald/95 p-3 text-center text-[0.55rem] leading-relaxed font-semibold tracking-[0.18em] text-white uppercase shadow-lg backdrop-blur-sm sm:right-6 sm:bottom-6 sm:size-28 lg:grid">
                        Tu estilo<br>en minutos
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($categories->isNotEmpty())
        @php($archTones = ['#1f5c45', '#8b7360', '#7da582', '#a48a73', '#3f6f58'])
        <section class="categories-section bg-cream py-6 sm:py-8 lg:py-10" aria-labelledby="universo-hade-title">
            <div class="store-container">
                <div class="mx-auto max-w-xl text-center">
                    <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Nuestras categorías</p>
                    <h2 id="universo-hade-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-ink">Universo Hade</h2>
                </div>

                <ul class="snap-row mt-4 -mx-4 justify-center-safe gap-2 px-4 sm:mt-5 sm:-mx-6 sm:gap-3 sm:px-6 lg:mx-0 lg:px-0">
                    @foreach ($categories as $category)
                        <li class="w-[34%] shrink-0 sm:w-[20%] lg:w-32">
                            <a
                                href="{{ route('categories.show', $category) }}"
                                class="group relative isolate flex aspect-[5/7] flex-col items-center justify-end overflow-hidden rounded-full px-2 pb-[18%] text-center text-white shadow-[0_12px_28px_-17px_rgba(23,23,23,0.45)] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald"
                                style="background-color: {{ $archTones[$loop->index % count($archTones)] }}"
                            >
                                @if ($category->image)
                                    <x-store.image
                                        :path="$category->image"
                                        :kind="ImageKind::Category"
                                        :width="480"
                                        sizes="(min-width: 1024px) 128px, (min-width: 640px) 20vw, 34vw"
                                        :alt="''"
                                        loading="lazy"
                                        class="absolute inset-0 -z-10 size-full object-cover transition-transform duration-1000 ease-out group-hover:scale-[1.025] motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                                    />
                                @else
                                    <span aria-hidden="true" class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_50%_22%,rgba(255,255,255,0.22),transparent_60%)]"></span>
                                    <span aria-hidden="true" class="absolute inset-x-0 top-[12%] -z-10 font-serif text-[4.2rem] leading-none text-white/12 transition-transform duration-1000 ease-out group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100 sm:text-[5.2rem]">{{ mb_substr($category->name, 0, 1) }}</span>
                                @endif
                                <span aria-hidden="true" class="absolute inset-0 -z-10 bg-linear-to-t from-ink/60 via-ink/10 to-transparent"></span>

                                <span class="text-balance font-serif text-[clamp(0.64rem,0.52rem+0.42vw,0.84rem)] leading-[1.05] tracking-[0.04em] uppercase">{{ $category->name }}</span>
                                <span class="mt-1.5 inline-flex items-center gap-0.5 text-[0.5rem] font-medium text-white/85 transition-colors duration-300 ease-out group-hover:text-white">
                                    Explorar
                                    <span aria-hidden="true" class="transition-transform duration-300 ease-out group-hover:translate-x-0.5 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @forelse ($sections as $section)
        @switch($section->type)
            @case(HomeSectionType::Offers)
                @php($products = $section->products(5))
                @if ($products->isNotEmpty())
                    <section class="bg-emerald py-10 sm:py-12 lg:py-14" aria-labelledby="seccion-{{ $section->id }}">
                        <div class="store-container">
                            <div class="mx-auto max-w-xl text-center text-white">
                                <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-sage uppercase">Kits y promociones</p>
                                <h2 id="seccion-{{ $section->id }}" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em]">Favoritos que combinan</h2>
                                @if ($section->subtitle)
                                    <p class="mt-2 text-sm text-white/75">{{ $section->subtitle }}</p>
                                @endif
                            </div>
                            <x-store.carousel label="Favoritos que combinan" :autoplay="true" :interval="5000" :merge-last-page="true" class="mt-6 gap-5 sm:mt-8">
                                @foreach ($products as $product)
                                    <x-store.product-card :product="$product" size="large" class="w-[80%] shrink-0 sm:w-[calc((100%-1.25rem)/2)] lg:w-[30%]" />
                                @endforeach
                                <a
                                    href="{{ route('products.index') }}"
                                    class="group flex w-[80%] shrink-0 flex-col items-center justify-center gap-4 px-6 text-center text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white sm:w-[calc((100%-1.25rem)/2)] lg:w-[30%]"
                                    aria-label="Ver todos los productos"
                                >
                                    <span class="grid size-14 place-items-center rounded-full bg-white text-emerald shadow-lg transition-transform duration-300 group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">
                                        <x-store.icon name="chevron-right" class="size-6" />
                                    </span>
                                    <span class="font-serif text-2xl font-medium">Ver más</span>
                                </a>
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
                @php($sectionHeading = $section->type === HomeSectionType::Latest ? 'Nuevos hallazgos' : $section->title)
                @if ($products->isNotEmpty())
                    @if ($section->type === HomeSectionType::Latest)
                        @once
                            <section class="bg-blush py-10 sm:py-12 lg:py-14" aria-labelledby="como-funciona-title">
                                <div class="store-container">
                                    <div class="mx-auto max-w-xl text-center">
                                        <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Tu ritual Hade</p>
                                        <h2 id="como-funciona-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-ink">Así de fácil. Así de Hade.</h2>
                                        <p class="mt-2 text-sm text-ink/60">Una manicure con acabado impecable, desde casa y en pocos minutos.</p>
                                    </div>

                                    <ol class="mx-auto mt-8 grid max-w-5xl grid-cols-2 gap-x-4 gap-y-10 sm:mt-10 sm:gap-x-8 lg:grid-cols-4 lg:gap-x-6 lg:gap-y-0">
                                        @foreach ([
                                            ['number' => '01', 'title' => 'Prepara', 'description' => 'Limpia y seca tus uñas; empuja suavemente las cutículas.'],
                                            ['number' => '02', 'title' => 'Elige y pega', 'description' => 'Escoge el tamaño correcto, colócalo desde la base y alisa hacia los bordes.'],
                                            ['number' => '03', 'title' => 'Recorta y lima', 'description' => 'Retira el excedente y lima hacia abajo siguiendo la forma de tu uña.'],
                                            ['number' => '04', 'title' => 'Cura y disfruta', 'description' => 'Pasa cada uña por la lámpara para fijar el gel. Tu diseño está listo.'],
                                        ] as $step)
                                            <li @class(['group flex text-center', 'flex-col' => $loop->odd, 'flex-col-reverse' => $loop->even])>
                                                <div class="mx-auto h-40 w-32 overflow-hidden rounded-t-full rounded-b-[1.5rem] shadow-[0_18px_40px_-24px_rgba(23,23,23,0.45)] sm:h-52 sm:w-42 lg:h-60 lg:w-48">
                                                    <img src="{{ asset('images/ritual/step-'.$loop->iteration.'.jpg') }}" alt="" loading="lazy" class="size-full object-cover transition duration-700 group-hover:scale-105 motion-reduce:transition-none">
                                                </div>

                                                <div @class(['min-h-28 sm:min-h-32', 'mt-5' => $loop->odd, 'mb-5' => $loop->even])>
                                                    <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Paso {{ $step['number'] }}</p>
                                                    <h3 class="mt-1 font-serif text-lg font-medium text-ink sm:text-xl">{{ $step['title'] }}</h3>
                                                    <p class="mx-auto mt-1 max-w-[15rem] text-xs leading-relaxed text-ink/60 sm:text-sm">{{ $step['description'] }}</p>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ol>

                                    <div class="mt-10 flex justify-center">
                                        <a href="{{ route('products.index') }}" class="group inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-emerald px-5 text-xs font-semibold text-white transition duration-300 hover:bg-[#174f3d] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald sm:text-sm">
                                            Encuentra tu diseño
                                            <x-store.icon name="chevron-right" class="size-3.5 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" />
                                        </a>
                                    </div>
                                </div>
                            </section>
                        @endonce
                    @endif

                    <section @class([
                        'pt-12' => $section->type !== HomeSectionType::Latest,
                        'scalloped-section z-10 bg-cream py-12' => $section->type === HomeSectionType::Latest,
                    ]) aria-labelledby="seccion-{{ $section->id }}">
                        <div class="store-container">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    @if ($section->type === HomeSectionType::Latest)
                                        <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Novedades</p>
                                    @endif
                                    <h2 id="seccion-{{ $section->id }}" @class([
                                        'section-heading' => $section->type !== HomeSectionType::Latest,
                                        'mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em]' => $section->type === HomeSectionType::Latest,
                                    ])>{{ $sectionHeading }}</h2>
                                    @if ($section->subtitle)
                                        <p class="mt-1 text-sm text-ink/60">{{ $section->subtitle }}</p>
                                    @endif
                                </div>
                                <a href="{{ $section->category ? route('categories.show', $section->category) : route('products.index') }}" class="shrink-0 text-sm text-ink/70 hover:text-ink">Ver más</a>
                            </div>
                            <x-store.carousel :label="$sectionHeading" class="mt-5">
                                @foreach ($products as $product)
                                    <x-store.product-card :product="$product" :size="$section->type === HomeSectionType::Latest ? 'latest' : 'base'" class="w-40 shrink-0 sm:w-48 lg:w-[calc(20%-0.8rem)]" />
                                @endforeach
                            </x-store.carousel>
                        </div>
                    </section>

                    @if ($section->type === HomeSectionType::Latest)
                        @once
                            <section class="relative isolate flex min-h-[21.6rem] items-center overflow-hidden text-center text-white sm:min-h-72 lg:min-h-[21.6rem]" aria-labelledby="catalogo-cta">
                                <img src="{{ asset('images/hero-nail-stickers.jpg') }}" alt="" loading="lazy" class="absolute inset-0 -z-20 size-full object-cover object-center">
                                <span class="absolute inset-0 -z-10 bg-[#6f5e50]/20" aria-hidden="true"></span>
                                <span class="absolute inset-0 -z-10 bg-linear-to-b from-[#b9a28f]/55 via-[#8b7360]/65 to-[#514840]/75" aria-hidden="true"></span>

                                <div class="store-container py-12 sm:py-14">
                                    <div class="mx-auto flex max-w-3xl flex-col items-center gap-3.5">
                                        <p class="text-[0.62rem] font-semibold tracking-[0.24em] text-white/85 uppercase">Lista para encontrar tu próximo favorito</p>
                                        <h2 id="catalogo-cta" class="text-balance font-serif text-[clamp(2.25rem,1.6rem+2.4vw,3.5rem)] leading-tight font-medium tracking-[-0.03em]">Pequeños detalles, grandes expresiones.</h2>
                                        <p class="text-balance text-sm leading-relaxed text-white/80 sm:text-base md:whitespace-nowrap">Descubre el universo Hade y encuentra diseños, kits y esenciales hechos para ti.</p>
                                        <div class="mt-3 flex flex-wrap justify-center gap-3">
                                            <a href="{{ route('products.index') }}" class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-white px-7 text-sm font-semibold text-taupe transition duration-300 hover:bg-beige focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                                                Entrar a la tienda
                                                <x-store.icon name="chevron-right" class="size-4 transition-transform duration-300 group-hover:translate-x-0.5" />
                                            </a>
                                            <a href="#seccion-{{ $section->id }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/70 px-7 text-sm font-semibold text-white transition duration-300 hover:border-white hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                                                Ver novedades
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endonce
                    @endif
                @endif
        @endswitch
    @empty
        <section class="store-container py-24 text-center">
            <p class="font-serif text-2xl">Muy pronto</p>
            <p class="mt-2 text-ink/60">Estamos preparando la tienda.</p>
        </section>
    @endforelse

</x-store-layout>
