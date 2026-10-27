@php
    use App\Enums\ImageKind;
    use App\Support\ImageOptimizer;
    use App\Support\Price;

    $gallery = $product->galleryPaths();
    $settings = \App\Models\StoreSetting::current();
    $whatsappUrl = $settings->whatsappUrl("Hola, quiero pedir: {$product->name} (".Price::format($product->price).")\n".route('products.show', $product));
    $shareDescription = filled(strip_tags((string) $product->description))
        ? $product->description
        : $product->name.' a '.Price::format($product->price).'. '.$settings->footer_text;
    $cartItem = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (float) $product->price,
        'url' => route('products.show', $product),
        'image' => $product->coverUrl(320),
    ];
@endphp

<x-store-layout :title="$product->name" :description="$shareDescription" :image="$product->coverUrl()" :price="$product->price" :flush-footer="$related->isNotEmpty()">
    <div class="store-container min-w-0 pt-4 sm:pt-8">
        <nav class="flex min-w-0 items-center text-xs text-ink/60" aria-label="Ruta">
            <a href="{{ route('home') }}" class="shrink-0 hover:text-ink">Inicio</a>
            @if ($product->category)
                <span class="mx-1 shrink-0">/</span>
                <a href="{{ route('categories.show', $product->category) }}" class="max-w-[35%] shrink truncate hover:text-ink">{{ $product->category->name }}</a>
            @endif
            <span class="mx-1 shrink-0">/</span>
            <span class="min-w-0 truncate" aria-current="page">{{ $product->name }}</span>
        </nav>

        <div class="mt-3 grid min-w-0 gap-7 sm:mt-5 md:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] md:items-start md:gap-8 lg:gap-14">
            {{-- Gallery: big photo plus thumbnails, switched by resources/js/gallery.js --}}
            <div class="min-w-0" data-gallery>
                <div class="relative aspect-square overflow-hidden rounded-2xl bg-tile sm:rounded-3xl">
                    <x-store.image
                        :path="$gallery[0] ?? $product->coverPath()"
                        :kind="$gallery !== [] ? ImageKind::Product : $product->coverKind()"
                        :width="800"
                        sizes="(min-width: 1280px) 600px, (min-width: 768px) 50vw, 100vw"
                        :alt="$product->name"
                        class="size-full object-cover"
                        data-gallery-main
                    />
                    @if ($product->isOnSale())
                        <span class="absolute top-0 left-0 rounded-br-2xl bg-ink/50 px-3 py-2 text-sm font-bold text-white">-{{ $product->discountPercentage() }} %</span>
                    @endif
                </div>

                @if (count($gallery) > 1)
                    <ul class="mt-3 flex max-w-full snap-x snap-mandatory gap-3 overflow-x-auto px-1 pt-1 pb-3 sm:mt-4" aria-label="Imágenes de {{ $product->name }}">
                        @foreach ($gallery as $index => $photo)
                            <li class="w-[4.5rem] shrink-0 snap-start sm:w-24">
                                <button
                                    type="button"
                                    @class(['block aspect-square w-full overflow-hidden rounded-xl bg-tile ring-2 ring-offset-2 ring-offset-cream transition hover:ring-ink/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald sm:rounded-2xl', 'ring-ink' => $index === 0, 'ring-transparent' => $index > 0])
                                    data-gallery-thumb="{{ ImageOptimizer::url($photo, 800) }}"
                                    data-gallery-srcset="{{ ImageOptimizer::srcset($photo, ImageKind::Product) }}"
                                    aria-label="Ver foto {{ $index + 1 }}"
                                    aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                >
                                    <img src="{{ ImageOptimizer::url($photo, 320) }}" alt="" loading="lazy" class="size-full object-cover">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="min-w-0">
                <h1 class="break-words text-2xl font-bold sm:text-3xl">{{ $product->name }}</h1>
                @if ($product->category)
                    <a href="{{ route('categories.show', $product->category) }}" class="mt-3 inline-block rounded-full bg-tile px-3 py-1 text-xs text-ink/70 transition hover:bg-sage-light hover:text-ink">{{ $product->category->name }}</a>
                @endif

                <p class="mt-6 text-sm text-ink/60">Precio unitario</p>
                <x-store.price :product="$product" size="detail" class="mt-1" />
                @if ($product->isOnSale())
                    <p class="mt-2 inline-block rounded-full bg-sage-light px-3 py-1 text-sm font-semibold text-emerald">¡Ahorra {{ Price::format($product->savings()) }}!</p>
                @endif

                @if ($product->is_in_stock)
                    <form class="mt-6 space-y-3 sm:mt-8" data-add-to-cart="{{ json_encode($cartItem) }}">
                        <div class="flex items-center justify-between gap-4 sm:justify-start">
                            <label for="cantidad" class="text-sm text-ink/70">Cantidad</label>
                            <div class="flex items-center overflow-hidden rounded-full border border-ink/15 bg-white shadow-sm">
                                <button type="button" class="grid size-11 place-items-center rounded-full transition hover:bg-sage-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald" data-quantity-step="-1" aria-label="Quitar uno"><x-store.icon name="minus" class="size-4" /></button>
                                <input id="cantidad" name="quantity" type="number" min="1" max="99" value="1" inputmode="numeric" class="w-12 [appearance:textfield] bg-transparent text-center text-sm focus:outline-none [&::-webkit-inner-spin-button]:appearance-none" data-quantity>
                                <button type="button" class="grid size-11 place-items-center rounded-full transition hover:bg-sage-light focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald" data-quantity-step="1" aria-label="Agregar uno"><x-store.icon name="plus" class="size-4" /></button>
                            </div>
                        </div>

                        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-burgundy px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy">Agregar al carrito</button>
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-whatsapp min-h-12 w-full rounded-full py-3.5">
                                <x-store.icon name="whatsapp" class="size-5" />
                                Pedir por WhatsApp
                            </a>
                        @endif
                    </form>
                @else
                    <p class="mt-8 rounded-2xl border border-sale/30 bg-sale/5 px-4 py-3 text-sm font-semibold text-sale" role="status">Agotado: este producto no tiene stock por ahora.</p>
                    @if ($whatsappUrl)
                        <a href="{{ $settings->whatsappUrl("Hola, ¿cuándo vuelve a estar disponible {$product->name}?") }}" target="_blank" rel="noopener" class="btn-outline mt-3 w-full rounded-full py-3.5">Avísame por WhatsApp</a>
                    @endif
                @endif

                <section class="mt-7 border-t border-ink/10 pt-6 sm:mt-8" aria-labelledby="despacho">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="despacho" class="font-serif text-lg font-semibold">Opciones de despacho</h2>
                        <a href="{{ route('pages.show', 'envios-y-delivery') }}" class="text-xs font-semibold text-emerald underline decoration-emerald/30 underline-offset-4 transition hover:decoration-emerald">Ver detalles</a>
                    </div>

                    <ul class="mt-3 divide-y divide-ink/10 border-t border-ink/10 sm:border-y">
                        <li class="flex gap-3 py-4 sm:gap-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-sage-light text-emerald sm:size-11">
                                <x-store.icon name="truck" class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold">Envío a domicilio</h3>
                                <p class="mt-1 text-xs leading-relaxed text-ink/60 sm:text-sm">Hacemos delivery a todo el Perú. El costo y el plazo se confirman según tu ubicación.</p>
                            </div>
                        </li>
                        <li class="flex gap-3 py-4 sm:gap-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-beige text-taupe sm:size-11">
                                <x-store.icon name="check" class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold">Despacho coordinado</h3>
                                <p class="mt-1 text-xs leading-relaxed text-ink/60 sm:text-sm">Al completar tu pedido por WhatsApp coordinamos contigo la alternativa disponible y todos los detalles.</p>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>

        <section class="mt-10 border-t border-ink/10 py-8 sm:mt-14 sm:border-y sm:py-11 lg:py-12" aria-labelledby="descripcion">
            <div class="grid min-w-0 gap-6 sm:gap-7 lg:grid-cols-[minmax(0,0.7fr)_minmax(0,1.6fr)] lg:gap-0">
                <div>
                    <span class="block h-px w-12 bg-emerald" aria-hidden="true"></span>
                    <p class="mt-4 text-xs font-semibold tracking-[0.18em] text-emerald uppercase">Detalles del producto</p>
                    <h2 id="descripcion" class="mt-2 max-w-xs font-serif text-2xl font-semibold leading-tight sm:text-3xl">Descripción</h2>
                </div>

                <div class="min-w-0 lg:border-l lg:border-ink/10 lg:pl-12">
                    @if (filled(strip_tags((string) $product->description)))
                        <div class="rich-text max-w-3xl break-words">{!! str($product->description)->sanitizeHtml() !!}</div>
                    @else
                        <p class="max-w-2xl text-[0.95rem] leading-relaxed text-ink/65">Pronto agregaremos más información sobre este producto. Si tienes alguna consulta, puedes escribirnos por WhatsApp.</p>
                    @endif
                </div>
            </div>
        </section>
    </div>

    @if ($related->isNotEmpty())
        <section class="scalloped-section relative z-10 mt-12 bg-blush py-10 [--scallop-color:var(--color-blush)] sm:mt-16 sm:py-16" aria-labelledby="relacionados">
            <div class="store-container">
                <p class="text-xs font-semibold tracking-[0.18em] text-taupe uppercase">Sigue descubriendo</p>
                <h2 id="relacionados" class="mt-2 font-serif text-xl font-semibold sm:text-2xl">También te puede gustar</h2>
                <x-store.carousel label="También te puede gustar" class="mt-5">
                    @foreach ($related as $relatedProduct)
                        <x-store.product-card :product="$relatedProduct" size="related" class="w-[76vw] max-w-64 shrink-0 sm:w-52 md:w-56 lg:w-[calc(20%-0.8rem)] lg:max-w-none" />
                    @endforeach
                </x-store.carousel>
            </div>
        </section>
    @endif
</x-store-layout>
