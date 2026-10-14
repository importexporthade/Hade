@props(['product', 'size' => 'base'])

<p {{ $attributes->class(['flex flex-wrap items-baseline gap-x-2', 'justify-center' => $size === 'large']) }}>
    @if ($product->isOnSale())
        <span @class(['font-serif font-semibold text-sale', 'text-xl' => $size === 'large', 'text-base' => $size === 'base', 'text-3xl' => $size === 'detail'])>
            {{ \App\Support\Price::format($product->price) }}
        </span>
        <span @class(['font-serif text-ink/50 line-through', 'text-sm' => $size !== 'detail', 'text-lg' => $size === 'detail'])>
            <span class="sr-only">Antes</span>
            {{ \App\Support\Price::format($product->compare_at_price) }}
        </span>
    @else
        <span @class(['font-semibold', 'text-xl font-serif' => $size === 'large', 'text-sm' => $size === 'base', 'text-3xl font-serif' => $size === 'detail'])>
            {{ \App\Support\Price::format($product->price) }}
        </span>
    @endif
</p>
