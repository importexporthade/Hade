@props(['products', 'cardSize' => 'base'])

@if ($products->isEmpty())
    {{ $empty ?? '' }}
@else
    <ul class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        @foreach ($products as $product)
            <li class="flex">
                <x-store.product-card :product="$product" :size="$cardSize" class="w-full" />
            </li>
        @endforeach
    </ul>

    @if ($products instanceof \Illuminate\Contracts\Pagination\Paginator && $products->hasPages())
        <div class="mt-10">{{ $products->links() }}</div>
    @endif
@endif
