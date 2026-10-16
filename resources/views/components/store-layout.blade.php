<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

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
    <body class="flex min-h-dvh flex-col" data-whatsapp-number="{{ preg_replace('/\D+/', '', (string) $settings->whatsapp_number) }}">
        <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Ir al contenido</a>

        @if ($settings->announcement)
            <div class="bg-emerald px-4 py-2 text-center text-xs font-medium tracking-wide text-cream">
                {{ $settings->announcement }}
            </div>
        @endif

        <header>
            {{-- Photo band with the round logo sitting on its lower edge --}}
            <div class="relative h-36 bg-taupe sm:h-48">
                @if ($settings->header_image)
                    <x-store.image :path="$settings->header_image" :kind="\App\Enums\ImageKind::Header" :width="1200" sizes="100vw" class="size-full object-contain" />
                @endif

                <a href="{{ route('home') }}" class="absolute bottom-0 left-1/2 z-10 grid size-20 -translate-x-1/2 translate-y-1/2 place-items-center overflow-hidden rounded-full bg-cream ring-4 ring-cream sm:size-24" aria-label="{{ $settings->store_name }}, ir al inicio">
                    @if ($settings->logo)
                        <x-store.image :path="$settings->logo" :kind="\App\Enums\ImageKind::Logo" :width="192" sizes="96px" :alt="$settings->store_name" class="size-full object-cover" />
                    @else
                        <span class="grid size-full place-items-center rounded-full bg-emerald font-serif text-4xl text-cream">{{ mb_substr($settings->store_name, 0, 1) }}</span>
                    @endif
                </a>
            </div>

            <div class="border-b border-ink/5 bg-white">
                <div class="store-container flex h-14 items-center justify-between gap-4">
                    <button type="button" class="-ms-2 p-2 md:invisible" data-menu-toggle aria-controls="menu-movil" aria-expanded="false">
                        <x-store.icon name="menu" class="size-6" />
                        <span class="sr-only">Menú</span>
                    </button>

                    <div class="flex items-center gap-2 sm:gap-4">
                        <form action="{{ route('products.index') }}" method="get" role="search" class="hidden items-center gap-2 border-b border-transparent focus-within:border-ink/30 md:flex">
                            <x-store.icon name="search" class="size-5 text-ink/70" />
                            <label for="buscar" class="sr-only">Buscar productos</label>
                            <input id="buscar" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar" class="w-40 bg-transparent py-1 text-sm placeholder:text-ink/60 focus:outline-none">
                        </form>

                        <button type="button" class="relative p-2" data-cart-open aria-controls="carrito">
                            <x-store.icon name="cart" class="size-6" />
                            <span class="absolute top-0 right-0 grid min-w-4 place-items-center rounded-full bg-sage px-1 text-[0.65rem] font-bold text-white" data-cart-count>0</span>
                            <span class="sr-only">Abrir carrito</span>
                        </button>
                    </div>
                </div>
            </div>

            <nav class="border-b border-ink/5 bg-white" aria-label="Principal">
                <ul id="menu-movil" class="store-container hidden flex-col gap-1 py-3 text-[0.95rem] md:flex md:flex-row md:items-center md:gap-8 md:py-0" data-menu>
                    <li class="pb-2 md:hidden">
                        <form action="{{ route('products.index') }}" method="get" role="search" class="flex items-center gap-2 border border-ink/15 px-3">
                            <x-store.icon name="search" class="size-5 text-ink/70" />
                            <label for="buscar-movil" class="sr-only">Buscar productos</label>
                            <input id="buscar-movil" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos" class="w-full bg-transparent py-2.5 text-sm placeholder:text-ink/60 focus:outline-none">
                        </form>
                    </li>
                    <li><a href="{{ route('home') }}" class="block py-2 md:py-3.5">Inicio</a></li>

                    <li class="group relative">
                        <button type="button" class="flex items-center gap-1 py-2 md:py-3.5" aria-haspopup="true">
                            Categorías <x-store.icon name="chevron-down" class="size-4" />
                        </button>
                        <ul class="z-30 hidden min-w-56 bg-white ps-4 group-focus-within:block group-hover:block md:absolute md:top-full md:-left-4 md:ps-0 md:shadow-lg md:ring-1 md:ring-ink/5">
                            @foreach ($categories as $category)
                                <li><a href="{{ route('categories.show', $category) }}" class="block px-4 py-2.5 text-sm hover:bg-tile">{{ $category->name }}</a></li>
                            @endforeach
                            <li><a href="{{ route('products.index') }}" class="block border-t border-ink/5 px-4 py-2.5 text-sm font-semibold hover:bg-tile">Ver todos los productos</a></li>
                        </ul>
                    </li>

                    @if ($pages->isNotEmpty())
                        <li class="group relative">
                            <button type="button" class="flex items-center gap-1 py-2 md:py-3.5" aria-haspopup="true">
                                Información <x-store.icon name="chevron-down" class="size-4" />
                            </button>
                            <ul class="z-30 hidden min-w-56 bg-white ps-4 group-focus-within:block group-hover:block md:absolute md:top-full md:-left-4 md:ps-0 md:shadow-lg md:ring-1 md:ring-ink/5">
                                @foreach ($pages as $page)
                                    <li><a href="{{ route('pages.show', $page) }}" class="block px-4 py-2.5 text-sm hover:bg-tile">{{ $page->title }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                </ul>
            </nav>
        </header>

        <main id="contenido" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-ink/10 bg-white">
            <div class="store-container grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-1">
                    <p class="font-serif text-2xl text-emerald">{{ $settings->store_name }}</p>
                    @if ($settings->footer_text)
                        <p class="mt-3 text-sm leading-relaxed text-ink/70">{{ $settings->footer_text }}</p>
                    @endif
                </div>

                <div>
                    <h2 class="text-sm font-bold">Categorías</h2>
                    <ul class="mt-3 space-y-2 text-sm text-ink/70">
                        @foreach ($categories as $category)
                            <li><a href="{{ route('categories.show', $category) }}" class="hover:text-ink">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                @php($footerPages = $pages->where('show_in_footer', true))
                <div>
                    <h2 class="text-sm font-bold">Información</h2>
                    <ul class="mt-3 space-y-2 text-sm text-ink/70">
                        @foreach ($footerPages as $page)
                            <li><a href="{{ route('pages.show', $page) }}" class="hover:text-ink">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                    {{-- Stores in Peru must show the complaints book where customers can see it --}}
                    <a href="{{ route('complaints.create') }}" class="mt-4 inline-flex items-center gap-2 border border-ink/15 px-3 py-2 text-sm font-medium hover:border-ink/40">
                        <x-store.icon name="book" class="size-5 text-emerald" />
                        Libro de Reclamaciones
                    </a>
                </div>

                <div>
                    <h2 class="text-sm font-bold">Contáctanos</h2>
                    <ul class="mt-3 space-y-2 text-sm text-ink/70">
                        @if ($settings->email)
                            <li><a href="mailto:{{ $settings->email }}" class="flex items-center gap-2 break-all hover:text-ink"><x-store.icon name="envelope" class="size-4 shrink-0" />{{ $settings->email }}</a></li>
                        @endif
                        @if ($settings->phone)
                            <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $settings->phone) }}" class="flex items-center gap-2 hover:text-ink"><x-store.icon name="phone" class="size-4 shrink-0" />{{ $settings->phone }}</a></li>
                        @endif
                    </ul>
                    @php($socials = array_filter(['Facebook' => $settings->facebook_url, 'Instagram' => $settings->instagram_url, 'TikTok' => $settings->tiktok_url, 'YouTube' => $settings->youtube_url]))
                    @if ($socials !== [])
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($socials as $network => $url)
                                <li><a href="{{ $url }}" target="_blank" rel="noopener" class="inline-block border border-ink/15 px-2.5 py-1 text-xs font-medium hover:border-ink/40">{{ $network }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <p class="border-t border-ink/5 py-4 text-center text-xs text-ink/50">© {{ now()->year }} {{ $settings->store_name }}</p>
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
                    <button type="button" class="btn-whatsapp w-full" data-cart-checkout>
                        <x-store.icon name="whatsapp" class="size-5" />
                        Pedir por WhatsApp
                    </button>
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
