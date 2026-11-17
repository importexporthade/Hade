<x-store-layout title="Preguntas frecuentes" description="Respuestas sobre pedidos, pagos, envíos y cómo aplicar tus stickers de uñas.">
    <section class="bg-cream pt-5 sm:pt-7" aria-labelledby="frequent-questions-title">
        <div class="store-container">
            <div class="relative isolate min-h-56 overflow-hidden rounded-[2rem] bg-blush shadow-[0_18px_45px_-32px_rgba(23,23,23,0.4)] sm:min-h-64">
                <img src="{{ asset('images/moods/flower-corner.webp') }}" alt="" aria-hidden="true" width="720" height="900" class="pointer-events-none absolute -right-8 -bottom-10 h-[85%] w-auto opacity-60 select-none sm:-right-4 sm:-bottom-16 sm:h-[135%] sm:opacity-100">

                <div class="relative z-10 flex min-h-56 max-w-2xl flex-col justify-center px-5 py-8 sm:min-h-64 sm:px-9 sm:py-10 lg:px-12">
                    <nav class="text-[0.68rem] text-burgundy/65" aria-label="Ruta">
                        <a href="{{ route('home') }}" class="transition-colors hover:text-burgundy">Inicio</a>
                        <span class="mx-1.5" aria-hidden="true">/</span>
                        <span aria-current="page">Preguntas frecuentes</span>
                    </nav>

                    <p class="mt-5 text-[0.55rem] font-semibold tracking-[0.24em] text-burgundy/70 uppercase">Resolvemos tus dudas</p>
                    <h1 id="frequent-questions-title" class="mt-1 text-balance font-serif text-[clamp(2.15rem,1.6rem+2.3vw,3.5rem)] leading-[0.98] font-medium tracking-[-0.04em] text-burgundy">Preguntas frecuentes</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="store-container pt-8 sm:pt-10">
        <div>
            <x-store.faq-accordion :questions="$questions" />

            @if ($whatsappUrl)
                <div class="mt-8 flex flex-col items-center gap-3 text-center">
                    <p class="text-sm text-ink/65">¿No encontraste tu respuesta?</p>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-whatsapp rounded-full">
                        <x-store.icon name="whatsapp" class="size-5" />
                        Escríbenos por WhatsApp
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Lets search engines show the questions and answers right in the results --}}
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions->map(fn ($question): array => [
                '@type' => 'Question',
                'name' => $question->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $question->plainAnswer()],
            ])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
</x-store-layout>
