@if ($paginator->hasPages())
    <nav class="flex max-w-full justify-center-safe overflow-x-auto px-1 pb-2" role="navigation" aria-label="Paginación">
        <span class="inline-flex shrink-0 divide-x divide-ink/15 overflow-hidden rounded-xl border border-ink/15 bg-white shadow-[0_8px_20px_-12px_rgba(23,23,23,0.45)]">
            @if ($paginator->onFirstPage())
                <span class="grid min-h-12 min-w-12 cursor-not-allowed place-items-center bg-cream/65 text-ink/25" aria-disabled="true" aria-label="Página anterior">
                    <x-store.icon name="chevron-left" class="size-5" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid min-h-12 min-w-12 place-items-center text-ink/60 transition-colors hover:bg-rose-light hover:text-burgundy focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy" aria-label="Página anterior">
                    <x-store.icon name="chevron-left" class="size-5" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="grid min-h-12 min-w-12 place-items-center px-3 text-sm font-medium text-ink/40" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="grid min-h-12 min-w-12 place-items-center bg-rose-light px-3 text-sm font-semibold text-burgundy" aria-current="page" aria-label="Página {{ $page }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="grid min-h-12 min-w-12 place-items-center px-3 text-sm font-medium text-ink/70 transition-colors hover:bg-blush hover:text-burgundy focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid min-h-12 min-w-12 place-items-center text-ink/60 transition-colors hover:bg-rose-light hover:text-burgundy focus-visible:z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-burgundy" aria-label="Página siguiente">
                    <x-store.icon name="chevron-right" class="size-5" />
                </a>
            @else
                <span class="grid min-h-12 min-w-12 cursor-not-allowed place-items-center bg-cream/65 text-ink/25" aria-disabled="true" aria-label="Página siguiente">
                    <x-store.icon name="chevron-right" class="size-5" />
                </span>
            @endif
        </span>
    </nav>
@endif
