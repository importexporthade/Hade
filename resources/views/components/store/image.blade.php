@props(['path', 'kind', 'width' => null, 'sizes' => '100vw', 'alt' => ''])

{{--
    A stored photo with every copy listed, so the browser downloads the one that fits the
    screen; "width" picks the copy used where srcset is not understood. Without a photo it
    shows the store's placeholder.
--}}
@php
    use App\Support\ImageOptimizer;

    $srcset = $path ? ImageOptimizer::srcset($path, $kind) : null;
@endphp

<img
    src="{{ $path ? ImageOptimizer::url($path, $width) : asset('images/product-placeholder.svg') }}"
    @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
    alt="{{ $alt }}"
    {{ $attributes }}
>
