@php
    use App\Enums\HomeSectionType;
    use App\Enums\ImageKind;
@endphp

<x-store-layout :flush-footer="true">
    @if ($banners->isNotEmpty())
        <x-store.hero-slider :banners="$banners" />
    @else
        <section class="relative isolate overflow-hidden bg-ivory" aria-labelledby="hero-title">
            <div class="grid min-h-[clamp(28rem,68svh,36rem)] min-w-0 lg:mx-auto lg:min-h-[32rem] lg:max-w-7xl lg:grid-cols-[0.9fr_1.1fr] xl:max-w-[90rem]">
                <div class="relative z-10 col-start-1 row-start-1 flex min-w-0 flex-col justify-end gap-4 px-5 pb-[clamp(2.5rem,8svh,5rem)] sm:px-7 lg:justify-center lg:gap-0 lg:px-10 lg:py-14 xl:px-14">
                    <p class="order-2 flex items-center gap-2 text-[0.625rem] font-semibold tracking-[0.24em] text-emerald uppercase sm:text-[0.68rem] lg:order-none lg:gap-2.5">
                        <span>Fáciles</span>
                        <span aria-hidden="true" class="size-1 rounded-full bg-sage-dark"></span>
                        <span>Elegantes</span>
                        <span aria-hidden="true" class="size-1 rounded-full bg-sage-dark"></span>
                        <span>Tuyas</span>
                    </p>

                    <h1 id="hero-title" class="order-1 max-w-[12ch] text-balance font-serif text-[clamp(2.25rem,calc(1.16rem+5.45vw),2.625rem)] leading-[0.98] font-medium tracking-[-0.045em] text-ink lg:order-none lg:mt-4 lg:max-w-2xl lg:text-[clamp(2.6rem,4.7vw,4.75rem)] lg:leading-[0.95] lg:text-burgundy">
                        Uñas con estilo para cada momento
                    </h1>

                    <p class="mt-4 hidden max-w-lg text-sm leading-relaxed text-ink/70 sm:text-base lg:block">
                        Diseños que se aplican en minutos, duran por días y hacen que cada look se sienta completamente tuyo.
                    </p>

                    <div class="order-3 lg:order-none lg:mt-6">
                        <a href="{{ route('products.index') }}" class="group inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-burgundy px-5 text-xs font-semibold text-white shadow-[0_10px_24px_rgba(106,30,46,0.18)] transition hover:-translate-y-0.5 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy lg:min-h-12 lg:gap-2.5 lg:px-6 lg:text-sm">
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
                    <div aria-hidden="true" class="absolute inset-y-0 left-0 w-[84%] bg-linear-to-r from-ivory/95 via-ivory/72 to-transparent lg:hidden"></div>
                    <div aria-hidden="true" class="absolute inset-y-0 left-0 hidden w-20 bg-linear-to-r from-ivory to-transparent lg:block"></div>

                    <div class="absolute right-4 bottom-4 hidden size-24 place-items-center rounded-full border border-white/35 bg-emerald/95 p-3 text-center text-[0.55rem] leading-relaxed font-semibold tracking-[0.18em] text-white uppercase shadow-lg backdrop-blur-sm sm:right-6 sm:bottom-6 sm:size-28 lg:grid">
                        Tu estilo<br>en minutos
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($categories->isNotEmpty())
        @php($archTones = ['#6A1E2E', '#D8A3A6', '#2F5D4E', '#C8B6A8', '#8A4855'])
        <section class="categories-section bg-cream py-6 sm:py-8 lg:py-10" aria-labelledby="universo-hade-title">
            <div class="store-container">
                <div class="mx-auto max-w-xl text-center">
                    <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Nuestras categorías</p>
                    <h2 id="universo-hade-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-ink">Universo Hade</h2>
                </div>

                <ul class="snap-row mt-4 -mb-8 justify-center-safe gap-2 pt-2 pb-10 sm:mt-3 sm:gap-3">
                    @foreach ($categories as $category)
                        <li class="w-[34%] shrink-0 sm:w-[20%] lg:w-32">
                            <a
                                href="{{ route('categories.show', $category) }}"
                                class="group relative isolate flex aspect-[5/7] flex-col items-center justify-end overflow-hidden rounded-full px-2 pb-[18%] text-center text-white shadow-[0_14px_32px_-18px_rgba(74,30,40,0.35)] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald"
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
                            <div class="max-w-xl text-left text-white">
                                @if (mb_strtolower($section->type->getLabel()) !== mb_strtolower($section->title))
                                    <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-sage uppercase">{{ $section->type->getLabel() }}</p>
                                @endif
                                <h2 id="seccion-{{ $section->id }}" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em]">{{ $section->title }}</h2>
                                @if ($section->subtitle)
                                    <p class="mt-2 text-sm text-white/75">{{ $section->subtitle }}</p>
                                @endif
                            </div>
                            <x-store.carousel :label="$section->title" :autoplay="true" :interval="5000" :merge-last-page="true" class="mt-6 gap-5 sm:mt-8">
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

            @default
                {{-- Every product section shares one design: the heading comes from the manager, the small label names its category, and the background alternates cream and green --}}
                @php($isManicureSection = $section->type === HomeSectionType::Category && str_contains(mb_strtolower($section->category?->name ?? ''), 'manicur'))
                @php($products = $section->products(5))
                @php($sectionHeading = $section->title)
                @php($sectionLabel = $section->category?->name ?? $section->type->getLabel())
                @php($sectionLabel = mb_strtolower($sectionLabel) === mb_strtolower($sectionHeading) ? null : $sectionLabel)
                @php($sectionLink = $section->category ? route('categories.show', $section->category) : ($section->type === HomeSectionType::Latest ? route('products.index', ['collection' => 'latest']) : route('products.index')))
                @if ($products->isNotEmpty())
                    @php($productSectionCount = ($productSectionCount ?? 0) + 1)
                    @php($isDarkSection = $productSectionCount % 2 === 0)
                    @if ($section->type === HomeSectionType::Latest)
                        @once
                            <section class="relative isolate overflow-hidden bg-blush py-10 sm:py-12 lg:py-14" aria-labelledby="como-funciona-title">
                                <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -top-10 -left-10 -z-10 h-56 w-auto rotate-180 opacity-50 select-none sm:h-80 lg:-top-16 lg:h-96 lg:opacity-70">
                                <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -right-10 -bottom-10 -z-10 h-64 w-auto opacity-50 select-none sm:h-96 lg:-bottom-16 lg:h-[30rem] lg:opacity-70">

                                <div class="store-container">
                                    <div class="mx-auto max-w-xl text-center">
                                        <p class="text-[0.5rem] font-semibold tracking-[0.24em] text-emerald uppercase">Tu ritual Hade</p>
                                        <h2 id="como-funciona-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-ink">Así de fácil. Así de Hade.</h2>
                                        <p class="mt-2 text-sm text-ink/70">Una manicure con acabado impecable, desde casa y en pocos minutos.</p>
                                    </div>

                                    <div class="mx-auto mt-8 max-w-5xl sm:mt-10">
                                        <ol class="grid grid-cols-2 gap-x-5 gap-y-10 sm:gap-x-8 sm:gap-y-12 lg:grid-cols-4 lg:gap-x-6">
                                            @foreach ([
                                                ['number' => '01', 'title' => 'Prepara', 'description' => 'Limpia y seca tus uñas; empuja suavemente las cutículas.'],
                                                ['number' => '02', 'title' => 'Elige y pega', 'description' => 'Escoge el tamaño correcto, colócalo desde la base y alisa hacia los bordes.'],
                                                ['number' => '03', 'title' => 'Recorta y lima', 'description' => 'Retira el excedente y lima hacia abajo siguiendo la forma de tu uña.'],
                                                ['number' => '04', 'title' => 'Cura y disfruta', 'description' => 'Pasa cada uña por la lámpara para fijar el gel. Tu diseño está listo.'],
                                            ] as $step)
                                                <li @class(['group text-center', 'mt-8 lg:mt-14' => $loop->even])>
                                                    <div @class([
                                                        'ritual-shape mx-auto aspect-[5/6] w-full max-w-56 overflow-hidden shadow-[0_22px_45px_-28px_rgba(110,30,50,0.5)] transition-transform duration-700 ease-out group-hover:rotate-0 motion-reduce:transition-none',
                                                        '-rotate-3' => $loop->odd,
                                                        'rotate-3' => $loop->even,
                                                    ]) style="--ritual-delay: -{{ $loop->index * 3.5 }}s">
                                                        <img src="{{ asset('images/ritual/step-'.$loop->iteration.'.jpg') }}" alt="" loading="lazy" class="size-full object-cover transition duration-700 group-hover:scale-105 motion-reduce:transition-none">
                                                    </div>

                                                    <div class="mt-6">
                                                        <p class="font-serif text-xl leading-none text-burgundy/60 italic"><span class="sr-only">Paso </span>{{ $step['number'] }}</p>
                                                        <h3 class="mt-1.5 font-serif text-lg leading-tight font-medium text-ink sm:text-xl">{{ $step['title'] }}</h3>
                                                        <p class="mx-auto mt-1.5 max-w-[15rem] text-xs leading-relaxed text-ink/70 sm:text-sm">{{ $step['description'] }}</p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>

                                    <div class="mt-10 flex justify-center lg:mt-12">
                                        <a href="{{ route('products.index') }}" class="group inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-burgundy px-5 text-xs font-semibold text-white transition duration-300 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy sm:text-sm">
                                            Encuentra tu diseño
                                            <x-store.icon name="chevron-right" class="size-3.5 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" />
                                        </a>
                                    </div>
                                </div>
                            </section>
                        @endonce
                    @endif

                    <section @class([
                        'scalloped-section z-10 bg-cream py-12' => ! $isDarkSection,
                        'bg-forest py-10 sm:py-12 lg:py-14' => $isDarkSection,
                    ]) aria-labelledby="seccion-{{ $section->id }}">
                        <div class="store-container">
                            @if ($sectionLabel)
                                <p @class([
                                    'text-[0.5rem] font-semibold tracking-[0.24em] uppercase',
                                    'text-rose-light' => $isDarkSection,
                                    'text-emerald' => ! $isDarkSection,
                                ])>{{ $sectionLabel }}</p>
                            @endif
                            <h2 id="seccion-{{ $section->id }}" @class([
                                'mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em]',
                                'text-white' => $isDarkSection,
                            ])>{{ $sectionHeading }}</h2>
                            @if ($section->subtitle)
                                <p @class(['mt-1 text-sm', 'text-white/75' => $isDarkSection, 'text-ink/60' => ! $isDarkSection])>{{ $section->subtitle }}</p>
                            @endif

                            <x-store.carousel :label="'Productos de '.$sectionHeading" :autoplay="true" :interval="5000" :merge-last-page="true" :arrows="false" class="mt-5 lg:grid lg:grid-cols-5 lg:gap-x-4 lg:gap-y-8 lg:overflow-visible lg:pb-0">
                                @foreach ($products as $product)
                                    <x-store.product-card :product="$product" size="latest" class="w-[76%] shrink-0 sm:w-[calc((100%-1rem)/2)] lg:w-auto" />
                                @endforeach
                            </x-store.carousel>

                            <div class="mt-8 flex justify-center">
                                <a href="{{ $sectionLink }}" aria-label="Ver más de {{ $sectionHeading }}" @class([
                                    'inline-flex min-h-11 items-center justify-center rounded-full px-6 text-sm font-semibold transition duration-300 focus-visible:outline-2 focus-visible:outline-offset-4',
                                    'bg-white text-forest hover:bg-rose-light focus-visible:outline-white' => $isDarkSection,
                                    'bg-burgundy text-white hover:bg-burgundy-dark focus-visible:outline-burgundy' => ! $isDarkSection,
                                ])>Ver más</a>
                            </div>
                        </div>
                    </section>

                    @if ($isManicureSection)
                        @once
                            @php($moods = [
                                ['key' => 'natural', 'label' => 'Natural', 'description' => 'Mano con uñas naturales'],
                                ['key' => 'minimal', 'label' => 'Minimal', 'description' => 'Mano con manicura francesa minimalista'],
                                ['key' => 'classic', 'label' => 'Classic', 'description' => 'Mano con uñas clásicas color borgoña'],
                                ['key' => 'print', 'label' => 'Print', 'description' => 'Mano con uñas de animal print'],
                                ['key' => 'glow', 'label' => 'Glow', 'description' => 'Mano con uñas azules brillantes'],
                            ])

                            <section
                                class="mood-showcase-section scalloped-section relative z-10 overflow-hidden bg-blush pt-12 sm:pt-16 lg:pt-20"
                                style="--mood-pattern-image: url('{{ asset('images/moods/patron-pink.png') }}?v={{ filemtime(public_path('images/moods/patron-pink.png')) }}')"
                                aria-labelledby="mood-showcase-title"
                                data-mood-showcase
                            >
                                <div class="store-container grid items-center gap-8 lg:grid-cols-[0.78fr_1.22fr] lg:gap-10">
                                    <div class="relative z-20 lg:py-6">
                                        <p class="text-[0.55rem] font-semibold tracking-[0.3em] text-emerald uppercase">Tu estilo Hade</p>
                                        <h2 id="mood-showcase-title" class="mt-2 max-w-[9ch] text-balance font-serif text-[clamp(2.75rem,2rem+3.1vw,5rem)] leading-[0.95] font-medium tracking-[-0.045em] text-ink">El detalle está en ti</h2>
                                        <p class="mt-5 max-w-md text-sm leading-relaxed text-ink/60 sm:text-base">Parte de una base natural y descubre cuatro estilos que hacen única cada manicura.</p>

                                        <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Elige el estilo de manicura">
                                            @foreach (array_slice($moods, 1) as $mood)
                                                <button
                                                    type="button"
                                                    class="min-h-10 rounded-full border border-taupe/25 bg-white/40 px-4 text-xs font-semibold text-ink/70 transition duration-300 hover:border-emerald/50 hover:bg-white/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald aria-pressed:border-emerald aria-pressed:bg-emerald aria-pressed:text-white sm:px-5 sm:text-sm"
                                                    data-mood-option="{{ $mood['key'] }}"
                                                    aria-pressed="false"
                                                >{{ $mood['label'] }}</button>
                                            @endforeach
                                        </div>

                                        <p class="sr-only" aria-live="polite" data-mood-status>Estilo Natural seleccionado.</p>

                                        <a href="{{ route('categories.show', $section->category) }}" class="group mt-8 inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-burgundy px-6 text-sm font-semibold text-white transition duration-300 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy">
                                            Explora tu estilo
                                            <x-store.icon name="chevron-right" class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none" />
                                        </a>
                                    </div>

                                    <div class="relative mx-auto aspect-[8/5] w-full max-w-3xl" aria-label="Vista previa del estilo seleccionado">
                                        <span class="absolute top-[10%] right-[4%] bottom-[2%] left-[4%] rounded-[50%] bg-white/45 lg:inset-y-[5%] lg:right-[1%] lg:left-[12%]" aria-hidden="true"></span>
                                        <span class="absolute top-[12%] right-[6%] h-[80%] w-[80%] rounded-full border border-taupe/15 lg:top-[8%] lg:right-[-8%] lg:h-[84%] lg:w-[64%]" aria-hidden="true"></span>
                                        <span class="absolute right-[6%] bottom-[8%] size-24 rounded-full bg-white/30 blur-2xl sm:size-36" aria-hidden="true"></span>

                                        @foreach ($moods as $mood)
                                            <img
                                                id="mood-image-{{ $mood['key'] }}"
                                                src="{{ asset('images/moods/'.$mood['key'].'.webp') }}?v={{ filemtime(public_path('images/moods/'.$mood['key'].'.webp')) }}"
                                                width="1920"
                                                height="1200"
                                                alt="{{ $mood['description'] }}"
                                                loading="lazy"
                                                decoding="async"
                                                class="absolute inset-0 z-10 size-full object-contain opacity-0 transition-opacity duration-500 ease-out motion-reduce:transition-none data-[active]:opacity-100"
                                                data-mood-image="{{ $mood['key'] }}"
                                                aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                                                @if ($loop->first) data-active @endif
                                            >
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        @endonce
                    @endif

                    @if ($section->type === HomeSectionType::Latest)
                        @once
                            <section class="relative isolate flex min-h-[19.44rem] items-center overflow-hidden text-center text-white sm:min-h-[16.2rem] lg:min-h-[19.44rem]" aria-labelledby="catalogo-cta">
                                <img src="{{ asset('images/hero-nail-stickers.jpg') }}" alt="" loading="lazy" class="absolute inset-0 -z-20 size-full object-cover object-center">
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

    {{-- The first questions of the "Preguntas frecuentes" page, so the owner edits them in one place --}}
    @if ($questions !== [])
        <section class="bg-blush pt-16 pb-14 sm:pt-18 lg:pt-20 lg:pb-18" aria-labelledby="preguntas-frecuentes-title">
            <div class="store-container grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:grid-rows-[auto_1fr] lg:gap-x-16">
                <div class="lg:col-start-1 lg:row-start-1">
                    <p class="text-[0.55rem] font-semibold tracking-[0.24em] text-forest uppercase">Preguntas frecuentes</p>
                    <h2 id="preguntas-frecuentes-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-burgundy">Resolvemos tus dudas</h2>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-ink/65">Lo que más nos preguntan antes de pedir sus stickers: pedidos, pagos, envíos y cómo aplicarlos.</p>
                </div>

                <div class="divide-y divide-rose/40 rounded-3xl border border-rose/40 bg-white px-5 shadow-[0_18px_40px_-30px_rgba(43,32,34,0.35)] sm:px-7 lg:col-start-2 lg:row-span-2 lg:row-start-1">
                    @foreach ($questions as $question)
                        <details class="group" name="preguntas-frecuentes">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-left text-[0.95rem] font-semibold text-ink transition-colors hover:text-burgundy focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy group-open:text-burgundy sm:py-5 [&::-webkit-details-marker]:hidden">
                                {{ $question['question'] }}
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-rose-light text-burgundy transition-transform duration-200 group-open:rotate-45 motion-reduce:transition-none" aria-hidden="true">
                                    <x-store.icon name="plus" class="size-4" />
                                </span>
                            </summary>
                            <div class="rich-text pb-5">
                                {!! $question['answer'] !!}
                            </div>
                        </details>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-3 lg:col-start-1 lg:row-start-2 lg:self-start">
                    <a href="{{ route('pages.show', $questionsPage) }}" class="btn-outline">Ver todas las preguntas</a>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-whatsapp rounded-full">
                            <x-store.icon name="whatsapp" class="size-5" />
                            ¿Otra duda? Escríbenos
                        </a>
                    @endif
                </div>
            </div>

            <script type="application/ld+json">
                {!! json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(fn (array $question): array => [
                        '@type' => 'Question',
                        'name' => $question['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => str(strip_tags(str_replace('<', ' <', $question['answer'])))->squish()->toString()],
                    ], $questions),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
            </script>
        </section>
    @endif

</x-store-layout>
