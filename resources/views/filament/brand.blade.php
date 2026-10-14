@php
    $logo = \App\Models\StoreSetting::current()->logo;
@endphp

<span class="hade-brand">
    @if ($logo)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="" class="hade-brand-mark hade-brand-mark-image">
    @else
        <span class="hade-brand-mark" aria-hidden="true">H</span>
    @endif

    <span class="hade-brand-text">
        <span class="hade-brand-name">Hade</span>
        <span class="hade-brand-tagline">Manager</span>
    </span>
</span>
