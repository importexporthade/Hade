@php
    $headerImage = \App\Models\StoreSetting::current()->header_image;
@endphp

{{-- The photo strip that crowns the storefront, repeated behind the login card. --}}
<div
    @class(['hade-band', 'hade-band-has-image' => $headerImage])
    @if ($headerImage)
        style="background-image: url('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($headerImage) }}')"
    @endif
    aria-hidden="true"
></div>
