<x-store-layout :title="$search !== '' ? 'Buscar: '.$search : 'Productos'">
    <div class="store-container pt-10">
        <h1 class="font-serif text-3xl">
            @if ($search !== '')
                Resultados para “{{ $search }}”
            @else
                Todos los productos
            @endif
        </h1>
        <p class="mt-1 text-sm text-ink/60">{{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}</p>

        <div class="mt-8">
            <x-store.product-grid :products="$products">
                <x-slot:empty>
                    <div class="py-16 text-center">
                        <p class="font-serif text-xl">No encontramos productos con ese nombre.</p>
                        <a href="{{ route('products.index') }}" class="btn-outline mt-6">Ver todos los productos</a>
                    </div>
                </x-slot:empty>
            </x-store.product-grid>
        </div>
    </div>
</x-store-layout>
