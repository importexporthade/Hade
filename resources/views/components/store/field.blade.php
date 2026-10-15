@props(['name', 'label', 'type' => 'text', 'hint' => null])

{{-- A labelled form field with its validation message; textarea when type is "textarea". --}}
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>

    @if ($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes->except('class')->merge(['class' => 'form-input', 'rows' => 5]) }}
        >{{ old($name) }}</textarea>
    @else
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name) }}"
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
            {{ $attributes->except('class')->merge(['class' => 'form-input']) }}
        >
    @endif

    @if ($hint)
        <p class="mt-1 text-xs text-ink/60">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="form-error">{{ $message }}</p>
    @enderror
</div>
