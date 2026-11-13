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
                        src="{{ asset('images/hero-nail-stickers.webp') }}"
                        srcset="{{ asset('images/hero-nail-stickers-768.webp') }} 768w, {{ asset('images/hero-nail-stickers.webp') }} 1536w"
                        sizes="(min-width: 1024px) 50vw, 100vw"
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

    @php($renderedSectionCount = 0)
    @foreach ($sections as $section)
        @php($sectionRendered = false)
        @switch($section->type)
            @case(HomeSectionType::Offers)
                @php($products = $productsBySection->get($section->getKey()))
                @if ($products->isNotEmpty())
                    @php($sectionRendered = true)
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
                @php($products = $productsBySection->get($section->getKey()))
                @php($sectionHeading = $section->title)
                @php($sectionLabel = $section->category?->name ?? $section->type->getLabel())
                @php($sectionLabel = mb_strtolower($sectionLabel) === mb_strtolower($sectionHeading) ? null : $sectionLabel)
                @php($sectionLink = $section->category ? route('categories.show', $section->category) : ($section->type === HomeSectionType::Latest ? route('products.index', ['collection' => 'latest']) : route('products.index')))
                @if ($products->isNotEmpty())
                    @php($sectionRendered = true)
                    @php($isDarkSection = $darkBackgroundsBySection[$section->getKey()])
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

                            <x-store.carousel :label="'Productos de '.$sectionHeading" :autoplay="true" :interval="5000" :merge-last-page="true" :arrows="false" class="mt-5 lg:justify-center lg:overflow-visible lg:pb-0">
                                @foreach ($products as $product)
                                    <x-store.product-card :product="$product" size="latest" class="w-[76%] shrink-0 sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-4rem)/5)]" />
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
                @endif

        @endswitch

        @if ($sectionRendered)
            @php($renderedSectionCount++)
            @switch($renderedSectionCount)
                @case(1)
                    <x-store.home-ritual />
                    @break

                @case(2)
                    <x-store.home-catalog-cta />
                    @break

                @case(3)
                    <x-store.home-mood-showcase />
                    @break
            @endswitch
        @endif
    @endforeach

    @if ($renderedSectionCount < 1)
        <x-store.home-ritual />
    @endif
    @if ($renderedSectionCount < 2)
        <x-store.home-catalog-cta />
    @endif
    @if ($renderedSectionCount < 3)
        <x-store.home-mood-showcase />
    @endif

    {{-- The first questions of the "Preguntas frecuentes" page, so the owner edits them in one place --}}
    @if ($questions !== [])
        <section class="bg-blush pt-16 pb-14 sm:pt-18 lg:pt-20 lg:pb-18" aria-labelledby="preguntas-frecuentes-title">
            <div class="store-container grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:grid-rows-[auto_1fr] lg:gap-x-16">
                <div class="lg:col-start-1 lg:row-start-1">
                    <p class="text-[0.55rem] font-semibold tracking-[0.24em] text-forest uppercase">Preguntas frecuentes</p>
                    <h2 id="preguntas-frecuentes-title" class="mt-1 font-serif text-[clamp(1.85rem,1.35rem+2vw,2.75rem)] leading-tight font-medium tracking-[-0.03em] text-burgundy">Resolvemos tus dudas</h2>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-ink/65">Lo que más nos preguntan antes de pedir sus stickers: pedidos, pagos, envíos y cómo aplicarlos.</p>
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

                <div class="divide-y divide-rose/40 rounded-3xl border border-rose/40 bg-white px-5 shadow-[0_18px_40px_-30px_rgba(43,32,34,0.35)] sm:px-7 lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-start" data-faq-accordion>
                    @foreach ($questions as $question)
                        <details class="group" name="preguntas-frecuentes" data-faq-item>
                            <summary class="group/trigger flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-left text-[0.95rem] font-semibold text-ink transition-colors hover:text-burgundy focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy group-open:text-burgundy sm:py-5 [&::-webkit-details-marker]:hidden" data-faq-trigger>
                                {{ $question['question'] }}
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-rose-light text-burgundy transition-transform duration-400 ease-[cubic-bezier(0.22,1,0.36,1)] group-aria-expanded/trigger:rotate-45 motion-reduce:transition-none" aria-hidden="true">
                                    <x-store.icon name="plus" class="size-4" />
                                </span>
                            </summary>
                            <div data-faq-panel>
                                <div class="rich-text pb-5">
                                    {!! $question['answer'] !!}
                                </div>
                            </div>
                        </details>
                    @endforeach
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
