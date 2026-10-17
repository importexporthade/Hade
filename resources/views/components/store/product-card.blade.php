@props(['product', 'size' => 'base'])

{{-- "large" is the big tile of the offers carousel; "base" the smaller tile of rows and grids. --}}
<article {{ $attributes->class(['group relative flex flex-col', 'bg-tile' => $size === 'large']) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden bg-tile">
        <x-store.image
            :path="$product->coverPath()"
            :kind="$product->coverKind()"
            :width="480"
            :sizes="$size === 'large' ? '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 80vw' : '(min-width: 1024px) 240px, (min-width: 640px) 192px, 160px'"
            :alt="$product->name"
            loading="lazy"
            class="size-full object-cover transition duration-500 group-hover:scale-105"
        />

        <span class="absolute top-0 left-0 flex flex-col items-start">
            @if ($product->isOnSale())
                <span class="bg-ink/50 px-2 py-1 text-xs font-bold text-white">-{{ $product->discountPercentage() }} %</span>
            @endif
            @unless ($product->is_in_stock)
                <span class="bg-sale px-2 py-1 text-xs font-bold text-white">Agotado</span>
            @endunless
        </span>
    </a>

    <div @class(['flex flex-1 flex-col', 'items-center px-2 py-5 text-center' => $size === 'large', 'pt-2' => $size === 'base'])>
        <h3 @class(['line-clamp-2', 'text-base font-medium' => $size === 'large', 'text-sm' => $size === 'base'])>
            <a href="{{ route('products.show', $product) }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        <x-store.price :product="$product" :size="$size" class="{{ $size === 'large' ? 'mt-4' : 'mt-1' }}" />
    </div>
</article>
