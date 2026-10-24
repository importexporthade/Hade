@php($flushFooter = (bool) $attributes->get('flush-footer', false))

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
        <link rel="icon" href="{{ \App\Support\Favicon::url(32) }}" sizes="32x32">
        <link rel="icon" href="{{ \App\Support\Favicon::url(192) }}" sizes="192x192">
        <link rel="apple-touch-icon" href="{{ \App\Support\Favicon::url(180) }}">
        <meta name="theme-color" content="#1f5c45">

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
            <div class="bg-emerald px-4 py-2 text-center text-xs font-medium tracking-wide text-cream">
                {{ $settings->announcement }}
            </div>
        @endif

        <header class="brand-nav relative z-20 border-b border-ink/10">
            @php($kitsCategory = $categories->first(fn ($category) => str_contains(mb_strtolower($category->name), 'kit')))

            <div class="store-container relative z-10 flex h-16 items-center justify-between lg:h-24 lg:justify-start lg:gap-4">
                <button type="button" class="-ms-2 justify-self-start p-2 lg:hidden" data-menu-toggle aria-controls="menu-movil" aria-expanded="false" aria-haspopup="true">
                    <x-store.icon name="menu" class="size-6" />
                    <span class="sr-only">Menú</span>
                </button>

                <a href="{{ route('home') }}" class="absolute left-1/2 flex shrink-0 -translate-x-1/2 items-center gap-2 lg:static lg:translate-x-0" aria-label="{{ $settings->store_name }}, ir al inicio">
                    @if ($logoUrl = $publicUrl($settings->logo))
                        <img src="{{ $logoUrl }}" alt="" class="h-11 w-auto lg:h-[4.5rem]">
                    @else
                        <x-store.logo :name="$settings->store_name" class="h-11 w-auto lg:h-[4.5rem]" aria-hidden="true" />
                    @endif
                    <span class="font-serif text-2xl leading-none font-medium tracking-[-0.04em] text-emerald uppercase lg:hidden">{{ $settings->store_name }}</span>
                </a>

                <nav class="mx-auto hidden lg:block" aria-label="Principal">
                    <ul class="flex items-center gap-7 text-sm xl:gap-10">
                        <li class="group relative">
                            <button type="button" class="flex items-center gap-1.5 py-9 transition hover:text-emerald" aria-haspopup="true">
                                Tienda <x-store.icon name="chevron-down" class="size-3.5" />
                            </button>
                            <ul class="absolute top-full left-1/2 z-30 hidden min-w-60 -translate-x-1/2 bg-white py-2 shadow-xl ring-1 ring-ink/5 group-focus-within:block group-hover:block">
                                @foreach ($categories as $category)
                                    <li><a href="{{ route('categories.show', $category) }}" class="block px-5 py-2.5 text-sm transition hover:bg-sage-light hover:text-emerald">{{ $category->name }}</a></li>
                                @endforeach
                                <li><a href="{{ route('products.index') }}" class="block border-t border-ink/5 px-5 py-2.5 text-sm font-semibold transition hover:bg-sage-light hover:text-emerald">Ver todos los productos</a></li>
                            </ul>
                        </li>
                        <li><a href="{{ route('products.index') }}" class="block py-9 transition hover:text-emerald">Más vendidos</a></li>
                        @if ($kitsCategory)
                            <li><a href="{{ route('categories.show', $kitsCategory) }}" class="block py-9 transition hover:text-emerald">Kits</a></li>
                        @endif
                        @if ($pages->isNotEmpty())
                            <li class="group relative">
                                <button type="button" class="flex items-center gap-1.5 py-9 transition hover:text-emerald" aria-haspopup="true">
                                    Información <x-store.icon name="chevron-down" class="size-3.5" />
                                </button>
                                <ul class="absolute top-full left-1/2 z-30 hidden min-w-56 -translate-x-1/2 bg-white py-2 shadow-xl ring-1 ring-ink/5 group-focus-within:block group-hover:block">
                                    @foreach ($pages as $page)
                                        <li><a href="{{ route('pages.show', $page) }}" class="block px-5 py-2.5 text-sm transition hover:bg-sage-light hover:text-emerald">{{ $page->title }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    </ul>
                </nav>

                <div class="ms-auto flex shrink-0 items-center lg:ms-0 lg:gap-3">
                    <form action="{{ route('products.index') }}" method="get" role="search" class="hidden h-11 items-center gap-2 rounded-full border border-ink/15 bg-white px-4 transition focus-within:border-emerald/50 md:flex">
                        <x-store.icon name="search" class="size-5 text-ink/70" />
                        <label for="buscar" class="sr-only">Buscar productos</label>
                        <input id="buscar" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar stickers..." class="w-36 bg-transparent text-sm placeholder:text-ink/45 focus:outline-none xl:w-48">
                    </form>

                    <a href="{{ route('products.index') }}" class="p-2 transition hover:text-emerald md:hidden" aria-label="Buscar productos">
                        <x-store.icon name="search" class="size-5" />
                    </a>

                    <button type="button" class="relative p-2 transition hover:text-emerald" data-cart-open aria-controls="carrito">
                        <x-store.icon name="cart" class="size-6" />
                        <span class="absolute top-0 right-0 grid min-w-4 place-items-center rounded-full bg-emerald px-1 text-[0.65rem] font-bold text-white" data-cart-count>0</span>
                        <span class="sr-only">Abrir carrito</span>
                    </button>
                </div>
            </div>

            <nav class="pointer-events-none absolute inset-x-0 top-full z-30 -translate-y-2 border-t border-ink/5 bg-cream/95 opacity-0 shadow-xl backdrop-blur-md transition-[opacity,transform] duration-200 ease-out data-[open=true]:pointer-events-auto data-[open=true]:translate-y-0 data-[open=true]:opacity-100 lg:hidden" aria-label="Principal móvil" aria-hidden="true" data-menu-panel data-open="false" inert>
                <ul id="menu-movil" class="store-container flex max-h-[calc(100dvh-5rem)] flex-col gap-1 overflow-y-auto py-3 text-[0.95rem]" data-menu>
                    <li class="pb-2 md:hidden">
                        <form action="{{ route('products.index') }}" method="get" role="search" class="flex items-center gap-2 rounded-full border border-ink/15 px-4">
                            <x-store.icon name="search" class="size-5 text-ink/70" />
                            <label for="buscar-movil" class="sr-only">Buscar productos</label>
                            <input id="buscar-movil" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar stickers..." class="w-full bg-transparent py-2.5 text-sm placeholder:text-ink/45 focus:outline-none">
                        </form>
                    </li>
                    <li><a href="{{ route('home') }}" class="block py-2.5">Inicio</a></li>
                    <li><a href="{{ route('products.index') }}" class="block py-2.5">Tienda</a></li>
                    @foreach ($categories as $category)
                        <li><a href="{{ route('categories.show', $category) }}" class="block py-2.5 ps-4 text-ink/70">{{ $category->name }}</a></li>
                    @endforeach
                    @if ($pages->isNotEmpty())
                        @foreach ($pages as $page)
                            <li><a href="{{ route('pages.show', $page) }}" class="block py-2.5">{{ $page->title }}</a></li>
                        @endforeach
                    @endif
                </ul>
            </nav>
        </header>

        <main id="contenido" class="min-w-0 flex-1 overflow-x-clip">
            {{ $slot }}
        </main>

        <footer @class([
            'bg-taupe text-white',
            'mt-16 border-t border-white/15' => ! $flushFooter,
        ])>
            <div class="store-container grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
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

                @php($footerPages = $pages->filter->isInFooter())
                <div>
                    <h2 class="text-sm font-bold">Información</h2>
                    <ul class="mt-3 space-y-2 text-sm text-white/70">
                        @foreach ($footerPages as $page)
                            <li><a href="{{ route('pages.show', $page) }}" class="hover:text-white">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                    {{-- Stores in Peru must show the complaints book where customers can see it --}}
                    <a href="{{ route('complaints.create') }}" class="mt-4 inline-flex items-center gap-2 border border-white/25 px-3 py-2 text-sm font-medium hover:border-white/60">
                        <x-store.icon name="book" class="size-5 text-white/80" />
                        Libro de Reclamaciones
                    </a>
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
            <div class="absolute inset-0 bg-ink/40" data-cart-close></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-ink/10 px-5 py-4">
                    <h2 id="carrito-titulo" class="font-serif text-lg">Carrito de compras</h2>
                    <button type="button" class="p-1" data-cart-close>
                        <x-store.icon name="x" class="size-6" />
                        <span class="sr-only">Cerrar carrito</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <div class="py-16 text-center" data-cart-empty>
                        <x-store.icon name="cart" class="mx-auto size-12 text-ink/20" />
                        <p class="mt-4 font-serif text-lg font-bold">Tu carrito está vacío</p>
                        <p class="mt-1 text-sm text-ink/60">Agrega productos para hacer tu pedido por WhatsApp.</p>
                    </div>
                    <ul class="divide-y divide-ink/10" data-cart-items></ul>
                </div>

                <div class="space-y-3 border-t border-ink/10 px-5 py-4" data-cart-footer hidden>
                    <div class="flex items-center justify-between">
                        <span class="text-sm">Total</span>
                        <span class="font-serif text-xl font-semibold" data-cart-total></span>
                    </div>
                    {{-- A real link rather than window.open, so phones open the WhatsApp app instead of blocking a pop-up --}}
                    <a href="#" target="_blank" rel="noopener" class="btn-whatsapp w-full" data-cart-checkout>
                        <x-store.icon name="whatsapp" class="size-5" />
                        Comprar por WhatsApp
                    </a>
                    <p class="text-center text-xs text-ink/60">Se abre WhatsApp con tu pedido escrito; solo envía el mensaje.</p>
                    <button type="button" class="w-full text-center text-sm text-ink/60 underline" data-cart-close>Seguir comprando</button>
                </div>
            </div>
        </div>

        <template id="cart-item-template">
            <li class="flex gap-3 py-4">
                <img alt="" class="size-20 shrink-0 bg-tile object-cover" data-item-image>
                <div class="flex min-w-0 flex-1 flex-col">
                    <a class="truncate text-sm font-medium" data-item-name></a>
                    <span class="mt-0.5 text-sm text-ink/60" data-item-price></span>
                    <div class="mt-auto flex items-center justify-between">
                        <div class="flex items-center border border-ink/15">
                            <button type="button" class="p-1.5" data-item-decrease aria-label="Quitar uno"><x-store.icon name="minus" class="size-4" /></button>
                            <span class="w-8 text-center text-sm" data-item-quantity></span>
                            <button type="button" class="p-1.5" data-item-increase aria-label="Agregar uno"><x-store.icon name="plus" class="size-4" /></button>
                        </div>
                        <button type="button" class="p-1.5 text-ink/50 hover:text-sale" data-item-remove aria-label="Quitar del carrito"><x-store.icon name="trash" class="size-5" /></button>
                    </div>
                </div>
            </li>
        </template>
    </body>
</html>
