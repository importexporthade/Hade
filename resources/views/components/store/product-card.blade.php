@props(['product', 'size' => 'base'])

@php
    $isLatest = $size === 'latest';
    $isLarge = $size === 'large';
    $isFramed = $isLatest || $isLarge;
    $cartItem = $isFramed ? [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (float) $product->price,
        'url' => route('products.show', $product),
        'image' => $product->coverUrl(320),
    ] : null;
@endphp

{{-- "large" and "latest" share the framed card and cart button at different scales. --}}
<article {{ $attributes->class([
    'group relative flex flex-col',
    'overflow-hidden rounded-2xl border border-ink/5 bg-white p-2 shadow-[0_12px_32px_-24px_rgba(23,23,23,0.35)]' => $isFramed,
]) }}>
    <a href="{{ route('products.show', $product) }}" @class(['relative block aspect-square overflow-hidden bg-tile', 'rounded-xl' => $isFramed])>
        <x-store.image
            :path="$product->coverPath()"
            :kind="$product->coverKind()"
            :width="480"
            :sizes="$size === 'large' ? '(min-width: 1280px) 240px, (min-width: 1024px) 20vw, (min-width: 640px) 50vw, 80vw' : '(min-width: 1024px) 240px, (min-width: 640px) 192px, 160px'"
            :alt="$product->name"
            loading="lazy"
            @class([
                'size-full object-cover transition-transform ease-out motion-reduce:transition-none motion-reduce:group-hover:scale-100',
                'duration-700 group-hover:scale-[1.025]' => $isFramed,
                'duration-500 group-hover:scale-105' => ! $isFramed,
            ])
        />

        <span class="absolute top-0 left-0 flex flex-col items-start">
            @if ($product->isOnSale())
                <span @class(['bg-ink/50 px-2 py-1 text-xs font-bold text-white', 'rounded-br-lg' => $isFramed])>-{{ $product->discountPercentage() }} %</span>
            @endif
            @unless ($product->is_in_stock)
                <span @class(['bg-sale px-2 py-1 text-xs font-bold text-white', 'rounded-br-lg' => $isFramed])>Agotado</span>
            @endunless
        </span>
    </a>

    <div @class([
        'flex flex-1 flex-col',
        'items-center px-3 pt-4 pb-2 text-center' => $isLarge,
        'pt-2' => $size === 'base',
        'px-1 pt-3 pb-1' => $isLatest,
    ])>
        <h3 @class(['line-clamp-2', 'text-base font-medium' => $isLarge, 'text-sm' => $size === 'base', 'min-h-9 text-xs leading-snug font-semibold' => $isLatest])>
            <a href="{{ route('products.show', $product) }}" @class(['after:absolute after:inset-0' => ! $isFramed])>{{ $product->name }}</a>
        </h3>
        <x-store.price :product="$product" :size="$isLatest ? 'base' : $size" @class(['mt-3' => $isLarge, 'mt-1' => $size === 'base', 'mt-2' => $isLatest]) />

        @if ($isFramed)
            @if ($product->is_in_stock)
                <form @class(['relative z-10 mt-auto w-full', 'pt-5' => $isLarge, 'pt-4' => $isLatest]) data-add-to-cart="{{ json_encode($cartItem) }}">
                    <button type="submit" @class([
                        'flex w-full items-center justify-center gap-1.5 bg-emerald font-semibold text-white transition-colors duration-300 hover:bg-[#174f3d] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald',
                        'min-h-11 rounded-xl px-4 text-sm' => $isLarge,
                        'min-h-9 rounded-lg px-2 text-[0.7rem]' => $isLatest,
                    ])>
                        <x-store.icon name="cart" @class(['size-4' => $isLarge, 'size-3.5' => $isLatest]) />
                        Agregar al carrito
                    </button>
                </form>
            @else
                <span @class(['mt-auto w-full', 'pt-5' => $isLarge, 'pt-4' => $isLatest])>
                    <span @class([
                        'flex items-center justify-center bg-ink/8 font-semibold text-ink/45',
                        'min-h-11 rounded-xl px-4 text-sm' => $isLarge,
                        'min-h-9 rounded-lg px-2 text-[0.7rem]' => $isLatest,
                    ])>Agotado</span>
                </span>
            @endif
        @endif
    </div>
</article>
