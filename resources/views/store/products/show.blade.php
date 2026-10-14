@php
    use App\Support\Price;

    $images = $product->imageUrls();
    $settings = \App\Models\StoreSetting::current();
    $whatsappUrl = $settings->whatsappUrl("Hola, quiero pedir: {$product->name} (".Price::format($product->price).")\n".route('products.show', $product));
    $plainDescription = str(strip_tags((string) $product->description))->squish()->limit(155)->toString();
    $cartItem = [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (float) $product->price,
        'url' => route('products.show', $product),
        'image' => $images[0],
    ];
@endphp

<x-store-layout :title="$product->name" :description="$plainDescription ?: null" :image="$images[0]">
    <div class="store-container pt-8">
        <nav class="text-xs text-ink/60" aria-label="Ruta">
            <a href="{{ route('home') }}" class="hover:text-ink">Inicio</a>
            @if ($product->category)
                <span class="mx-1">/</span>
                <a href="{{ route('categories.show', $product->category) }}" class="hover:text-ink">{{ $product->category->name }}</a>
            @endif
            <span class="mx-1">/</span>
            <span aria-current="page">{{ $product->name }}</span>
        </nav>

        <div class="mt-5 grid gap-8 md:grid-cols-2 lg:gap-14">
            {{-- Gallery: big photo plus thumbnails, switched by resources/js/gallery.js --}}
            <div data-gallery>
                <div class="relative aspect-square overflow-hidden bg-tile">
                    <img src="{{ $images[0] }}" alt="{{ $product->name }}" class="size-full object-cover" data-gallery-main>
                    @if ($product->isOnSale())
                        <span class="absolute top-0 left-0 bg-ink/50 px-2.5 py-1.5 text-sm font-bold text-white">-{{ $product->discountPercentage() }} %</span>
                    @endif
                </div>

                @if (count($images) > 1)
                    <ul class="mt-3 grid grid-cols-5 gap-2">
                        @foreach ($images as $index => $image)
                            <li>
                                <button type="button" @class(['block aspect-square w-full overflow-hidden bg-tile ring-2 ring-offset-2 ring-offset-cream', 'ring-ink' => $index === 0, 'ring-transparent' => $index > 0]) data-gallery-thumb="{{ $image }}" aria-label="Ver foto {{ $index + 1 }}">
                                    <img src="{{ $image }}" alt="" loading="lazy" class="size-full object-cover">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $product->name }}</h1>
                @if ($product->category)
                    <a href="{{ route('categories.show', $product->category) }}" class="mt-3 inline-block bg-tile px-2.5 py-1 text-xs text-ink/70 hover:text-ink">{{ $product->category->name }}</a>
                @endif

                <p class="mt-6 text-sm text-ink/60">Precio unitario</p>
                <x-store.price :product="$product" size="detail" class="mt-1" />
                @if ($product->isOnSale())
                    <p class="mt-2 inline-block bg-sage-light px-2.5 py-1 text-sm font-semibold text-emerald">¡Ahorra {{ Price::format($product->savings()) }}!</p>
                @endif

                @if ($product->is_in_stock)
                    <form class="mt-8 space-y-3" data-add-to-cart="{{ json_encode($cartItem) }}">
                        <div class="flex items-center gap-4">
                            <label for="cantidad" class="text-sm text-ink/70">Cantidad</label>
                            <div class="flex items-center border border-ink/15 bg-white">
                                <button type="button" class="p-2.5" data-quantity-step="-1" aria-label="Quitar uno"><x-store.icon name="minus" class="size-4" /></button>
                                <input id="cantidad" name="quantity" type="number" min="1" max="99" value="1" inputmode="numeric" class="w-12 [appearance:textfield] bg-transparent text-center text-sm focus:outline-none [&::-webkit-inner-spin-button]:appearance-none" data-quantity>
                                <button type="button" class="p-2.5" data-quantity-step="1" aria-label="Agregar uno"><x-store.icon name="plus" class="size-4" /></button>
                            </div>
                        </div>

                        <button type="submit" class="btn-sage w-full">Agregar al carrito</button>
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-whatsapp w-full">
                                <x-store.icon name="whatsapp" class="size-5" />
                                Pedir por WhatsApp
                            </a>
                        @endif
                    </form>
                @else
                    <p class="mt-8 border border-ink/10 bg-white px-4 py-3 text-sm">Este producto está agotado por ahora.</p>
                    @if ($whatsappUrl)
                        <a href="{{ $settings->whatsappUrl("Hola, ¿cuándo vuelve a estar disponible {$product->name}?") }}" target="_blank" rel="noopener" class="btn-outline mt-3 w-full">Avísame por WhatsApp</a>
                    @endif
                @endif

                @if (filled(strip_tags((string) $product->description)))
                    <section class="mt-10 border-t border-ink/10 pt-6" aria-labelledby="descripcion">
                        <h2 id="descripcion" class="font-bold">Descripción</h2>
                        <div class="rich-text mt-3">{!! str($product->description)->sanitizeHtml() !!}</div>
                    </section>
                @endif
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="pt-16" aria-labelledby="relacionados">
            <div class="store-container">
                <h2 id="relacionados" class="font-serif text-xl font-semibold">También te puede gustar</h2>
                <x-store.carousel label="También te puede gustar" class="mt-5">
                    @foreach ($related as $relatedProduct)
                        <x-store.product-card :product="$relatedProduct" class="w-40 shrink-0 sm:w-48 lg:w-[calc(20%-0.8rem)]" />
                    @endforeach
                </x-store.carousel>
            </div>
        </section>
    @endif
</x-store-layout>
