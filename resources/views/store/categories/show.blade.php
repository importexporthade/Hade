<x-store-layout :title="$category->name" :description="$category->description" :image="$category->imageUrl()">
    <div class="store-container pt-10">
        <nav class="text-xs text-ink/60" aria-label="Ruta">
            <a href="{{ route('home') }}" class="hover:text-ink">Inicio</a>
            <span class="mx-1">/</span>
            <span aria-current="page">{{ $category->name }}</span>
        </nav>

        <h1 class="mt-3 font-serif text-3xl">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-2 max-w-2xl text-sm text-ink/70">{{ $category->description }}</p>
        @endif

        <div class="mt-8">
            <x-store.product-grid :products="$products">
                <x-slot:empty>
                    <p class="py-16 text-center text-ink/60">Pronto tendremos productos en esta categoría.</p>
                </x-slot:empty>
            </x-store.product-grid>
        </div>
    </div>
</x-store-layout>
