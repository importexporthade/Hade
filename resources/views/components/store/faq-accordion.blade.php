@props(['questions'])

{{-- One answer open at a time; faq-accordion.js animates it and native <details> is the fallback --}}
<div {{ $attributes->class('divide-y divide-rose/40 rounded-3xl border border-rose/40 bg-white px-5 shadow-[0_18px_40px_-30px_rgba(43,32,34,0.35)] sm:px-7') }} data-faq-accordion>
    @foreach ($questions as $question)
        <details class="group" name="preguntas-frecuentes" data-faq-item>
            <summary class="group/trigger flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-left text-[0.95rem] font-semibold text-ink transition-colors hover:text-burgundy focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-burgundy group-open:text-burgundy sm:py-5 [&::-webkit-details-marker]:hidden" data-faq-trigger>
                {{ $question->question }}
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-rose-light text-burgundy transition-transform duration-400 ease-[cubic-bezier(0.22,1,0.36,1)] group-aria-expanded/trigger:rotate-45 motion-reduce:transition-none" aria-hidden="true">
                    <x-store.icon name="plus" class="size-4" />
                </span>
            </summary>
            <div data-faq-panel>
                <div class="rich-text pb-5">
                    {!! $question->safeAnswer() !!}
                </div>
            </div>
        </details>
    @endforeach
</div>
