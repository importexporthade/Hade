@php($moods = [
    ['key' => 'natural', 'label' => 'Natural', 'description' => 'Mano con uñas naturales'],
    ['key' => 'minimal', 'label' => 'Minimal', 'description' => 'Mano con manicura francesa minimalista'],
    ['key' => 'classic', 'label' => 'Classic', 'description' => 'Mano con uñas clásicas color borgoña'],
    ['key' => 'print', 'label' => 'Print', 'description' => 'Mano con uñas de animal print'],
    ['key' => 'glow', 'label' => 'Glow', 'description' => 'Mano con uñas azules brillantes'],
])

<section
    class="mood-showcase-section scalloped-section relative z-10 overflow-hidden bg-blush pt-12 sm:pt-16 lg:pt-20"
    style="--mood-pattern-image: url('{{ asset('images/moods/patron-pink.png') }}?v={{ filemtime(public_path('images/moods/patron-pink.png')) }}')"
    aria-labelledby="mood-showcase-title"
    data-mood-showcase
>
    <div class="store-container grid items-center gap-8 lg:grid-cols-[0.78fr_1.22fr] lg:gap-10">
        <div class="relative z-20 lg:py-6">
            <p class="text-[0.55rem] font-semibold tracking-[0.3em] text-emerald uppercase">Tu estilo Hade</p>
            <h2 id="mood-showcase-title" class="mt-2 max-w-[9ch] text-balance font-serif text-[clamp(2.75rem,2rem+3.1vw,5rem)] leading-[0.95] font-medium tracking-[-0.045em] text-ink">El detalle está en ti</h2>
            <p class="mt-5 max-w-md text-sm leading-relaxed text-ink/60 sm:text-base">Parte de una base natural y descubre cuatro estilos que hacen única cada manicura.</p>

            <div class="mt-6 flex flex-wrap gap-2" role="group" aria-label="Elige el estilo de manicura">
                @foreach (array_slice($moods, 1) as $mood)
                    <button
                        type="button"
                        class="min-h-10 rounded-full border border-taupe/25 bg-white/40 px-4 text-xs font-semibold text-ink/70 transition duration-300 hover:border-emerald/50 hover:bg-white/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald aria-pressed:border-emerald aria-pressed:bg-emerald aria-pressed:text-white sm:px-5 sm:text-sm"
                        data-mood-option="{{ $mood['key'] }}"
                        aria-pressed="false"
                    >{{ $mood['label'] }}</button>
                @endforeach
            </div>

            <p class="sr-only" aria-live="polite" data-mood-status>Estilo Natural seleccionado.</p>

            <a href="{{ route('products.index') }}" class="group mt-8 inline-flex min-h-12 items-center justify-center gap-3 rounded-full bg-burgundy px-6 text-sm font-semibold text-white transition duration-300 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy">
                Explora tu estilo
                <x-store.icon name="chevron-right" class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none" />
            </a>
        </div>

        <div class="relative mx-auto aspect-[8/5] w-full max-w-3xl" aria-label="Vista previa del estilo seleccionado">
            <span class="absolute top-[10%] right-[4%] bottom-[2%] left-[4%] rounded-[50%] bg-white/45 lg:inset-y-[5%] lg:right-[1%] lg:left-[12%]" aria-hidden="true"></span>
            <span class="absolute top-[12%] right-[6%] h-[80%] w-[80%] rounded-full border border-taupe/15 lg:top-[8%] lg:right-[-8%] lg:h-[84%] lg:w-[64%]" aria-hidden="true"></span>
            <span class="absolute right-[6%] bottom-[8%] size-24 rounded-full bg-white/30 blur-2xl sm:size-36" aria-hidden="true"></span>

            @foreach ($moods as $mood)
                <img
                    id="mood-image-{{ $mood['key'] }}"
                    src="{{ asset('images/moods/'.$mood['key'].'.webp') }}?v={{ filemtime(public_path('images/moods/'.$mood['key'].'.webp')) }}"
                    width="1920"
                    height="1200"
                    alt="{{ $mood['description'] }}"
                    loading="lazy"
                    decoding="async"
                    class="absolute inset-0 z-10 size-full object-contain opacity-0 transition-opacity duration-500 ease-out motion-reduce:transition-none data-[active]:opacity-100"
                    data-mood-image="{{ $mood['key'] }}"
                    aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                    @if ($loop->first) data-active @endif
                >
            @endforeach
        </div>
    </div>
</section>
