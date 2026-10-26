@php
    $flushFooter = (bool) $attributes->get('flush-footer', false);
@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="metrics-cart" content="{{ route('metrics.cart') }}">
        <meta name="metrics-checkout" content="{{ route('metrics.checkout') }}">

        <title>{{ $pageTitle() }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
            <meta property="og:description" content="{{ $description }}">
        @endif
        <link rel="icon" type="image/png" href="{{ asset('images/logos/favicon-vino-32.png') }}?v=2" sizes="32x32">
        <link rel="icon" type="image/png" href="{{ asset('images/logos/favicon-vino-192.png') }}?v=2" sizes="192x192">
        <link rel="apple-touch-icon" href="{{ asset('images/logos/favicon-vino-180.png') }}?v=2">
        <meta name="theme-color" content="#6A1E2E">

        <meta property="og:title" content="{{ $pageTitle() }}">
        <meta property="og:site_name" content="{{ $settings->store_name }}">
        @if ($shareImage = $image ?? $publicUrl($settings->logo))
            {{-- Social networks need a full address, not the relative one the page uses --}}
            <meta property="og:image" content="{{ url($shareImage) }}">
        @endif

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-dvh flex-col overflow-x-clip" data-whatsapp-number="{{ preg_replace('/\D+/', '', (string) $settings->whatsapp_number) }}">
        <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Ir al contenido</a>

        @if ($settings->announcement)
            <div class="bg-rose px-4 py-2 text-center text-xs font-medium tracking-wide text-burgundy">
                {{ $settings->announcement }}
            </div>
        @endif

        <header class="brand-nav sticky top-0 z-30 border-b border-ink/10 shadow-[0_1px_0_rgba(23,23,23,0.02)] backdrop-blur-xl">
            @php
                $kitsCategory = $categories->first(fn ($category) => str_contains(mb_strtolower($category->name), 'kit'));
                $nailCategories = $categories->filter(fn ($category) => str_contains(mb_strtolower($category->name), 'manicur') || str_contains(mb_strtolower($category->name), 'pedicur'));
                $complementCategories = $categories->filter(fn ($category) => str_contains(mb_strtolower($category->name), 'lámpara') || str_contains(mb_strtolower($category->name), 'lampara') || str_contains(mb_strtolower($category->name), 'accesorio'));
                $discoverCategories = $categories->whereNotIn('id', $nailCategories->merge($complementCategories)->modelKeys());
                $categoryGroups = [
                    ['label' => 'Para tus uñas', 'categories' => $nailCategories],
                    ['label' => 'Complementos', 'categories' => $complementCategories],
                    ['label' => 'Para descubrir', 'categories' => $discoverCategories],
                ];
                $latestIsActive = request()->routeIs('products.index') && request('collection') === 'latest';
                $storeIsActive = request()->routeIs('products.show', 'categories.*') || (request()->routeIs('products.index') && ! $latestIsActive);
                $informationIsActive = request()->routeIs('pages.*');
            @endphp

            <div class="store-container relative z-10 flex h-16 items-center justify-between lg:h-24 lg:justify-start lg:gap-5">
                <button type="button" class="group -ms-2 grid size-11 shrink-0 place-items-center rounded-full transition hover:bg-white/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald lg:hidden" data-menu-toggle aria-controls="menu-movil" aria-expanded="false" aria-haspopup="true">
                    <x-store.icon name="menu" class="size-6 group-aria-expanded:hidden" />
                    <x-store.icon name="x" class="hidden size-6 group-aria-expanded:block" />
                    <span class="sr-only" data-menu-label>Abrir menú</span>
                </button>

                <a href="{{ route('home') }}" class="absolute left-1/2 flex shrink-0 -translate-x-1/2 items-center gap-2 lg:static lg:translate-x-0" aria-label="{{ $settings->store_name }}, ir al inicio">
                    <img src="{{ asset('images/logos/logo-vino-transparente.png') }}" alt="" class="h-11 w-auto lg:h-[4.5rem]">
                    <span class="font-serif text-2xl leading-none font-medium tracking-[-0.04em] text-emerald uppercase lg:hidden">{{ $settings->store_name }}</span>
                </a>

                <nav class="mx-auto hidden self-stretch lg:flex" aria-label="Principal">
                    <ul class="flex h-full items-center gap-1.5 text-sm xl:gap-2">
                        <li class="group static flex items-center self-stretch" data-nav-dropdown>
                            <button type="button" @class([
                                'flex items-center gap-1.5 rounded-full px-4 py-2.5 font-medium tracking-[0.01em] text-ink/70 transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy',
                                'bg-rose-light text-burgundy shadow-sm hover:bg-rose' => $storeIsActive,
                                'hover:bg-rose-light hover:text-burgundy' => ! $storeIsActive,
                            ]) aria-controls="menu-tienda" aria-expanded="false" aria-haspopup="true" data-nav-trigger>
                                Tienda
                                <x-store.icon name="chevron-down" @class([
                                    'size-3.5 transition-transform duration-200 group-data-[open=true]:rotate-180 motion-reduce:transition-none',
                                    'text-burgundy/80' => $storeIsActive,
                                    'text-burgundy/70' => ! $storeIsActive,
                                ]) />
                            </button>
                            <div id="menu-tienda" class="pointer-events-none invisible absolute inset-x-0 top-full z-30 translate-y-2 pt-3 opacity-0 transition-[opacity,transform,visibility] duration-200 ease-out group-data-[open=true]:pointer-events-auto group-data-[open=true]:visible group-data-[open=true]:translate-y-0 group-data-[open=true]:opacity-100 motion-reduce:transition-none" data-nav-panel data-open="false">
                                <div class="grid min-h-40 grid-cols-[0.85fr_2fr_auto] overflow-hidden rounded-3xl border border-rose/30 bg-cream shadow-[0_22px_55px_rgba(23,23,23,0.16)]">
                                    <div class="flex flex-col justify-center border-r border-rose/40 bg-rose-light px-6 py-5">
                                        <p class="text-[0.62rem] font-bold tracking-[0.2em] text-burgundy/70 uppercase">Tienda Hade</p>
                                        <p class="mt-2 max-w-52 font-serif text-xl leading-tight font-medium text-burgundy">Encuentra tu próximo diseño.</p>
                                    </div>

                                    <div class="grid grid-cols-2 content-center gap-x-8 gap-y-4 px-6 py-5">
                                        @foreach ($categoryGroups as $group)
                                            @if ($group['categories']->isNotEmpty())
                                                <section @class([
                                                    'min-w-0',
                                                    'col-span-2 border-t border-rose/35 pt-4' => $group['label'] === 'Para descubrir',
                                                ]) aria-labelledby="menu-tienda-grupo-{{ $loop->index }}">
                                                    <h3 id="menu-tienda-grupo-{{ $loop->index }}" class="px-3 text-[0.6rem] font-bold tracking-[0.15em] text-burgundy uppercase">{{ $group['label'] }}</h3>
                                                    <ul class="mt-1">
                                                        @foreach ($group['categories'] as $category)
                                                            <li>
                                                                <a href="{{ route('categories.show', $category) }}" @class([
                                                                    'block rounded-xl px-3 py-2 text-sm text-ink/70 transition-colors duration-200 hover:bg-rose-light hover:text-burgundy focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy',
                                                                    'bg-rose font-semibold text-burgundy' => request()->routeIs('categories.show') && request()->route('category')?->is($category),
                                                                ])>
                                                                    {{ $category->name }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </section>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="flex items-center border-l border-rose/30 px-6 py-5">
                                        <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-forest px-5 text-sm font-semibold whitespace-nowrap text-cream transition-colors duration-200 hover:bg-forest-dark focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-forest">Ver todo el catálogo</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <a href="{{ route('products.index', ['collection' => 'latest']) }}" @class([
                                'block rounded-full px-4 py-2.5 font-medium tracking-[0.01em] text-ink/70 transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy',
                                'bg-rose-light text-burgundy shadow-sm hover:bg-rose' => $latestIsActive,
                                'hover:bg-rose-light hover:text-burgundy' => ! $latestIsActive,
                            ])>Novedades</a>
                        </li>
                        @if ($kitsCategory)
                            <li><a href="{{ route('categories.show', $kitsCategory) }}" @class([
                                'block rounded-full px-4 py-2.5 font-medium tracking-[0.01em] text-ink/70 transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy',
                                'bg-rose-light text-burgundy shadow-sm hover:bg-rose' => request()->routeIs('categories.show') && request()->route('category')?->is($kitsCategory),
                                'hover:bg-rose-light hover:text-burgundy' => ! (request()->routeIs('categories.show') && request()->route('category')?->is($kitsCategory)),
                            ])>Kits</a></li>
                        @endif
                        @if ($pages->isNotEmpty())
                            <li class="group static flex items-center self-stretch" data-nav-dropdown>
                                <button type="button" @class([
                                    'flex items-center gap-1.5 rounded-full px-4 py-2.5 font-medium tracking-[0.01em] text-ink/70 transition-colors duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy',
                                    'bg-rose-light text-burgundy shadow-sm hover:bg-rose' => $informationIsActive,
                                    'hover:bg-rose-light hover:text-burgundy' => ! $informationIsActive,
                                ]) aria-controls="menu-informacion" aria-expanded="false" aria-haspopup="true" data-nav-trigger>
                                    Información
                                    <x-store.icon name="chevron-down" @class([
                                        'size-3.5 transition-transform duration-200 group-data-[open=true]:rotate-180 motion-reduce:transition-none',
                                        'text-burgundy/80' => $informationIsActive,
                                        'text-burgundy/70' => ! $informationIsActive,
                                    ]) />
                                </button>
                                <div id="menu-informacion" class="pointer-events-none invisible absolute inset-x-0 top-full z-30 translate-y-2 pt-3 opacity-0 transition-[opacity,transform,visibility] duration-200 ease-out group-data-[open=true]:pointer-events-auto group-data-[open=true]:visible group-data-[open=true]:translate-y-0 group-data-[open=true]:opacity-100 motion-reduce:transition-none" data-nav-panel data-open="false">
                                    <div class="grid min-h-40 grid-cols-[0.85fr_2.4fr] overflow-hidden rounded-3xl border border-rose/30 bg-cream shadow-[0_22px_55px_rgba(23,23,23,0.16)]">
                                        <div class="flex flex-col justify-center border-r border-rose/40 bg-rose-light px-6 py-5">
                                            <p class="text-[0.62rem] font-bold tracking-[0.2em] text-burgundy/70 uppercase">Información</p>
                                            <p class="mt-2 max-w-52 font-serif text-xl leading-tight font-medium text-burgundy">Todo lo que necesitas saber.</p>
                                        </div>

                                        <ul class="grid grid-cols-2 content-center gap-1 px-6 py-4 xl:grid-cols-3">
                                            @foreach ($pages as $page)
                                                <li>
                                                    <a href="{{ route('pages.show', $page) }}" @class([
                                                        'block rounded-xl px-4 py-3 text-sm text-ink/70 transition-colors duration-200 hover:bg-rose-light hover:text-burgundy focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy',
                                                        'bg-rose font-semibold text-burgundy' => request()->routeIs('pages.show') && request()->route('page')?->is($page),
                                                    ])>
                                                        {{ $page->title }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif
                    </ul>
                </nav>

                <div class="ms-auto flex shrink-0 items-center lg:ms-0 lg:gap-2">
                    <form action="{{ route('products.index') }}" method="get" role="search" class="hidden h-11 items-center gap-2 rounded-full border border-ink/10 bg-white/80 px-4 shadow-sm transition focus-within:border-emerald/50 focus-within:ring-2 focus-within:ring-emerald/10 lg:flex">
                        <x-store.icon name="search" class="size-5 text-ink/70" />
                        <label for="buscar" class="sr-only">Buscar productos</label>
                        <input id="buscar" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar stickers..." class="w-32 bg-transparent text-sm placeholder:text-ink/45 focus:outline-none xl:w-40 2xl:w-48">
                    </form>

                    <button type="button" class="grid size-10 place-items-center rounded-full transition hover:bg-white hover:text-emerald focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald lg:hidden" aria-label="Buscar productos" aria-controls="menu-movil" aria-expanded="false" data-mobile-search-open>
                        <x-store.icon name="search" class="size-5" />
                    </button>

                    <button type="button" class="relative grid size-10 place-items-center rounded-full transition hover:bg-white hover:text-emerald focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald" data-cart-open aria-controls="carrito">
                        <x-store.icon name="cart" class="size-6" />
                        <span class="absolute -top-0.5 -right-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-emerald px-1 text-[0.62rem] leading-none font-bold text-white ring-2 ring-cream" data-cart-count>0</span>
                        <span class="sr-only">Abrir carrito</span>
                    </button>
                </div>
            </div>

            <nav class="pointer-events-none invisible absolute inset-x-0 top-full z-30 -translate-y-2 border-t border-ink/5 bg-cream/98 opacity-0 shadow-[0_20px_45px_rgba(23,23,23,0.16)] backdrop-blur-xl transition-[opacity,transform,visibility] duration-200 ease-out data-[open=true]:pointer-events-auto data-[open=true]:visible data-[open=true]:translate-y-0 data-[open=true]:opacity-100 motion-reduce:transition-none lg:hidden" aria-label="Principal móvil" aria-hidden="true" data-menu-panel data-open="false" inert>
                <div id="menu-movil" class="store-container max-h-[calc(100dvh-4rem)] overflow-y-auto py-4" data-menu>
                    <form action="{{ route('products.index') }}" method="get" role="search" class="mb-4 flex items-center gap-2 rounded-2xl border border-ink/10 bg-white px-4 shadow-sm md:hidden">
                        <x-store.icon name="search" class="size-5 text-ink/60" />
                        <label for="buscar-movil" class="sr-only">Buscar productos</label>
                        <input id="buscar-movil" type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando?" class="w-full bg-transparent py-3 text-sm placeholder:text-ink/40 focus:outline-none">
                    </form>

                    <ul class="flex flex-col gap-1 text-[0.95rem]">
                        <li data-mobile-submenu data-open="{{ $storeIsActive ? 'true' : 'false' }}">
                            <button type="button" class="group flex w-full items-center justify-between rounded-xl px-3 py-3 text-left font-medium transition hover:bg-rose-light focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy" aria-controls="menu-movil-tienda" aria-expanded="{{ $storeIsActive ? 'true' : 'false' }}" data-submenu-trigger>
                                <span @class(['text-burgundy' => $storeIsActive])>Tienda</span>
                                <span class="grid size-7 place-items-center rounded-full bg-rose-light text-burgundy">
                                    <x-store.icon name="chevron-down" class="size-3.5 transition-transform duration-200 group-aria-expanded:rotate-180 motion-reduce:transition-none" />
                                </span>
                            </button>
                            <div id="menu-movil-tienda" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-200 ease-out data-[open=true]:grid-rows-[1fr] motion-reduce:transition-none" aria-hidden="{{ $storeIsActive ? 'false' : 'true' }}" data-submenu-panel data-open="{{ $storeIsActive ? 'true' : 'false' }}" @if (! $storeIsActive) inert @endif>
                                <div class="overflow-hidden">
                                    <div class="mx-3 mb-2 border-l border-rose/40 pl-3">
                                        @foreach ($categoryGroups as $group)
                                            @if ($group['categories']->isNotEmpty())
                                                <section @class(['py-2', 'border-t border-ink/8' => ! $loop->first]) aria-labelledby="menu-movil-tienda-grupo-{{ $loop->index }}">
                                                    <h3 id="menu-movil-tienda-grupo-{{ $loop->index }}" class="px-3 pb-1 text-[0.58rem] font-bold tracking-[0.15em] text-burgundy/75 uppercase">{{ $group['label'] }}</h3>
                                                    <ul>
                                                        @foreach ($group['categories'] as $category)
                                                            <li>
                                                                <a href="{{ route('categories.show', $category) }}" @class([
                                                                    'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink/65 transition-colors hover:bg-rose-light hover:text-burgundy',
                                                                    'font-semibold text-burgundy' => request()->routeIs('categories.show') && request()->route('category')?->is($category),
                                                                ])>
                                                                    <span @class([
                                                                        'size-1.5 shrink-0 rounded-full bg-rose-dark',
                                                                        'invisible' => ! (request()->routeIs('categories.show') && request()->route('category')?->is($category)),
                                                                    ]) aria-hidden="true"></span>
                                                                    <span>{{ $category->name }}</span>
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </section>
                                            @endif
                                        @endforeach
                                        <div class="border-t border-ink/8 pt-1">
                                            <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-burgundy transition-colors hover:bg-rose-light">Ver todos los productos</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li>
                            <a href="{{ route('products.index', ['collection' => 'latest']) }}" @class([
                                'block rounded-xl px-3 py-3 font-medium transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-emerald',
                                'bg-white text-emerald shadow-sm' => $latestIsActive,
                            ])>
                                Novedades
                            </a>
                        </li>

                        @if ($kitsCategory)
                            <li>
                                <a href="{{ route('categories.show', $kitsCategory) }}" @class([
                                    'block rounded-xl px-3 py-3 font-medium transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-emerald',
                                    'bg-white text-emerald shadow-sm' => request()->routeIs('categories.show') && request()->route('category')?->is($kitsCategory),
                                ])>
                                    Kits
                                </a>
                            </li>
                        @endif

                        @if ($pages->isNotEmpty())
                            <li data-mobile-submenu data-open="{{ $informationIsActive ? 'true' : 'false' }}">
                                <button type="button" class="group flex w-full items-center justify-between rounded-xl px-3 py-3 text-left font-medium transition hover:bg-rose-light focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy" aria-controls="menu-movil-informacion" aria-expanded="{{ $informationIsActive ? 'true' : 'false' }}" data-submenu-trigger>
                                    <span @class(['text-burgundy' => $informationIsActive])>Información</span>
                                    <span class="grid size-7 place-items-center rounded-full bg-rose-light text-burgundy">
                                        <x-store.icon name="chevron-down" class="size-3.5 transition-transform duration-200 group-aria-expanded:rotate-180 motion-reduce:transition-none" />
                                    </span>
                                </button>
                                <div id="menu-movil-informacion" class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-200 ease-out data-[open=true]:grid-rows-[1fr] motion-reduce:transition-none" aria-hidden="{{ $informationIsActive ? 'false' : 'true' }}" data-submenu-panel data-open="{{ $informationIsActive ? 'true' : 'false' }}" @if (! $informationIsActive) inert @endif>
                                    <div class="overflow-hidden">
                                        <ul class="mx-3 mb-2 border-l border-rose/40 pl-3">
                                            @foreach ($pages as $page)
                                                <li>
                                                    <a href="{{ route('pages.show', $page) }}" @class([
                                                        'flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-ink/65 transition-colors hover:bg-rose-light hover:text-burgundy',
                                                        'font-semibold text-burgundy' => request()->routeIs('pages.show') && request()->route('page')?->is($page),
                                                    ])>
                                                        <span @class([
                                                            'size-1.5 shrink-0 rounded-full bg-rose-dark',
                                                            'invisible' => ! (request()->routeIs('pages.show') && request()->route('page')?->is($page)),
                                                        ]) aria-hidden="true"></span>
                                                        <span>{{ $page->title }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </nav>
        </header>

        <main id="contenido" class="min-w-0 flex-1 overflow-x-clip">
            {{ $slot }}
        </main>

        <footer @class([
            'bg-forest text-white',
            'mt-16 border-t border-white/15' => ! $flushFooter,
        ])>
            <div class="store-container grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-5 lg:gap-8">
                <div class="lg:col-span-1">
                    <p class="font-serif text-2xl">{{ $settings->store_name }}</p>
                    @if ($settings->footer_text)
                        <p class="mt-3 text-sm leading-relaxed text-white/70">{{ $settings->footer_text }}</p>
                    @endif
                </div>

                <div>
                    <h2 class="text-sm font-bold">Categorías</h2>
                    <ul class="mt-3 space-y-2 text-sm text-white/70">
                        @foreach ($categories as $category)
                            <li><a href="{{ route('categories.show', $category) }}" class="hover:text-white">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                @php
                    $legalLinks = [
                        ['label' => 'Política de privacidad', 'slug' => 'politica-de-privacidad'],
                        ['label' => 'Términos y condiciones', 'slug' => 'terminos-y-condiciones'],
                        ['label' => 'Cambios y devoluciones', 'slug' => 'cambios-y-devoluciones'],
                    ];
                    $legalPageSlugs = collect($legalLinks)->pluck('slug');
                    $footerPages = $pages->filter->isInFooter()->whereNotIn('slug', $legalPageSlugs);
                @endphp
                <div>
                    <h2 class="text-sm font-bold">Información</h2>
                    <ul class="mt-3 space-y-2 text-sm text-white/70">
                        @foreach ($footerPages as $page)
                            <li><a href="{{ route('pages.show', $page) }}" class="hover:text-white">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-bold">Legal</h2>
                    <ul class="mt-3 space-y-2 text-sm text-white/70">
                        @foreach ($legalLinks as $legalLink)
                            @php($legalPage = $pages->firstWhere('slug', $legalLink['slug']))
                            <li>
                                @if ($legalPage)
                                    <a href="{{ route('pages.show', $legalPage) }}" class="hover:text-white">{{ $legalLink['label'] }}</a>
                                @else
                                    <span class="cursor-not-allowed text-white/45" title="Contenido próximamente" aria-disabled="true">{{ $legalLink['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                        {{-- Stores in Peru must show the complaints book where customers can see it --}}
                        <li>
                            <a href="{{ route('complaints.create') }}" class="flex items-start gap-2 hover:text-white">
                                <x-store.icon name="book" class="mt-0.5 size-4 shrink-0" />
                                Libro de Reclamaciones
                            </a>
                        </li>
                        @if ($settings->legal_name)
                            <li>{{ $settings->legal_name }}</li>
                        @endif
                        @if ($settings->ruc)
                            <li>RUC {{ $settings->ruc }}</li>
                        @endif
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-bold">Contáctanos</h2>
                    <ul class="mt-3 space-y-2 text-sm text-white/70">
                        @if ($settings->email)
                            <li><a href="mailto:{{ $settings->email }}" class="flex items-center gap-2 break-all hover:text-white"><x-store.icon name="envelope" class="size-4 shrink-0" />{{ $settings->email }}</a></li>
                        @endif
                        @if ($settings->phone)
                            <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $settings->phone) }}" class="flex items-center gap-2 hover:text-white"><x-store.icon name="phone" class="size-4 shrink-0" />{{ $settings->phone }}</a></li>
                        @endif
                    </ul>
                    @php($socials = array_filter(['Facebook' => $settings->facebook_url, 'Instagram' => $settings->instagram_url, 'TikTok' => $settings->tiktok_url, 'YouTube' => $settings->youtube_url]))
                    @if ($socials !== [])
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($socials as $network => $url)
                                <li><a href="{{ $url }}" target="_blank" rel="noopener" class="inline-block border border-white/25 px-2.5 py-1 text-xs font-medium hover:border-white/60">{{ $network }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <p class="border-t border-white/15 py-4 text-center text-xs text-white/60">© {{ now()->year }} {{ $settings->store_name }}</p>
        </footer>

        @if ($settings->whatsappUrl())
            <a href="{{ $settings->whatsappUrl() }}" target="_blank" rel="noopener" class="fixed right-5 bottom-5 z-30 grid size-14 place-items-center rounded-full bg-whatsapp text-white shadow-lg transition hover:scale-105" aria-label="Escríbenos por WhatsApp">
                <x-store.icon name="whatsapp" class="size-8" />
            </a>
        @endif

        {{-- Cart drawer, filled by resources/js/cart.js from the browser's saved cart --}}
        <div id="carrito" class="fixed inset-0 z-40" hidden data-cart role="dialog" aria-modal="true" aria-labelledby="carrito-titulo">
            <div class="absolute inset-0 bg-ink/50 backdrop-blur-[2px]" data-cart-close></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-[30rem] flex-col border-l border-rose/30 bg-cream shadow-[-24px_0_60px_rgba(23,23,23,0.2)]">
                <div class="relative overflow-hidden border-b border-rose/30 bg-blush px-5 py-5 sm:px-6 sm:py-6">
                    <div class="relative flex items-start justify-between gap-5">
                        <div>
                            <p class="text-[0.55rem] font-semibold tracking-[0.24em] text-burgundy/70 uppercase">Tu selección</p>
                            <div class="mt-1 flex items-center gap-2.5">
                                <h2 id="carrito-titulo" class="font-serif text-2xl leading-tight font-medium text-burgundy">Carrito de compras</h2>
                                <span class="grid min-h-6 min-w-6 place-items-center rounded-full bg-burgundy px-1.5 text-[0.65rem] font-bold text-cream" data-cart-count>0</span>
                            </div>
                            <p class="mt-1.5 text-xs leading-relaxed text-ink/60">Revisa tus diseños antes de enviar el pedido.</p>
                        </div>

                        <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full bg-cream/80 text-burgundy transition hover:bg-cream focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy" data-cart-close>
                            <x-store.icon name="x" class="size-5" />
                            <span class="sr-only">Cerrar carrito</span>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-5 sm:px-6">
                    <div class="grid min-h-full place-items-center py-8 text-center" data-cart-empty>
                        <div class="w-full px-6">
                            <span class="mx-auto grid size-16 place-items-center rounded-full bg-cream text-burgundy shadow-sm ring-1 ring-rose/30">
                                <x-store.icon name="cart" class="size-7" />
                            </span>
                            <p class="mt-5 font-serif text-2xl font-medium text-burgundy">Tu carrito está vacío</p>
                            <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-ink/60">Descubre un diseño, agrégalo aquí y prepara tu pedido por WhatsApp.</p>
                            <a href="{{ route('products.index') }}" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-full bg-burgundy px-6 text-sm font-semibold text-cream transition hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy" data-cart-close>Explorar productos</a>
                        </div>
                    </div>
                    <ul class="space-y-3" data-cart-items></ul>
                </div>

                <div class="border-t border-rose/30 bg-blush/60 px-5 py-5 sm:px-6" data-cart-footer hidden>
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-[0.55rem] font-semibold tracking-[0.2em] text-burgundy/60 uppercase">Resumen del pedido</p>
                            <span class="mt-1 block text-sm text-ink/60">Total</span>
                        </div>
                        <span class="font-serif text-3xl leading-none font-semibold text-burgundy" data-cart-total></span>
                    </div>

                    <div class="mt-5 space-y-3">
                        {{-- A real link rather than window.open, so phones open the WhatsApp app instead of blocking a pop-up --}}
                        <a href="#" target="_blank" rel="noopener" class="btn-whatsapp min-h-12 w-full rounded-full shadow-[0_12px_28px_-16px_rgba(37,211,102,0.7)]" data-cart-checkout>
                            <x-store.icon name="whatsapp" class="size-5" />
                            Comprar por WhatsApp
                        </a>
                        <p class="text-center text-xs leading-relaxed text-ink/55">Tu pedido se abrirá listo en WhatsApp; solo tendrás que enviarlo.</p>
                        <button type="button" class="w-full rounded-full py-2 text-center text-sm font-medium text-burgundy transition hover:bg-cream/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy" data-cart-close>Seguir comprando</button>
                    </div>
                </div>
            </div>
        </div>

        <template id="cart-item-template">
            <li class="flex gap-3 rounded-2xl border border-rose/30 bg-white p-3 shadow-[0_12px_30px_-24px_rgba(23,23,23,0.4)] sm:gap-4">
                <img alt="" class="size-22 shrink-0 rounded-xl bg-tile object-cover sm:size-24" data-item-image>
                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <a class="line-clamp-2 text-sm leading-snug font-semibold text-ink transition-colors hover:text-burgundy" data-item-name></a>
                        <button type="button" class="grid size-8 shrink-0 place-items-center rounded-full text-ink/40 transition hover:bg-rose-light hover:text-sale focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sale" data-item-remove aria-label="Quitar del carrito"><x-store.icon name="trash" class="size-4" /></button>
                    </div>
                    <span class="mt-1 font-serif text-base font-semibold text-burgundy" data-item-price></span>

                    <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                        <div class="flex items-center rounded-full border border-rose/40 bg-cream">
                            <button type="button" class="grid size-8 place-items-center rounded-full text-burgundy transition hover:bg-rose-light focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-burgundy" data-item-decrease aria-label="Quitar uno"><x-store.icon name="minus" class="size-3.5" /></button>
                            <span class="w-8 text-center text-sm font-semibold text-ink" data-item-quantity></span>
                            <button type="button" class="grid size-8 place-items-center rounded-full text-burgundy transition hover:bg-rose-light focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-burgundy" data-item-increase aria-label="Agregar uno"><x-store.icon name="plus" class="size-3.5" /></button>
                        </div>
                        <span class="text-[0.65rem] font-medium tracking-[0.08em] text-ink/40 uppercase">Cantidad</span>
                    </div>
                </div>
            </li>
        </template>
    </body>
</html>
