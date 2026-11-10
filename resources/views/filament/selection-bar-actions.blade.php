{{--
    The bulk actions ("Oferta", "Borrar") sit beside "Deselecciona todos", where the owner is
    already looking once products are ticked, instead of in the toolbar above. The table still
    holds them as toolbar actions, so the toolbar copies are only hidden.
--}}
@php
    $selectionBarClass = \App\Filament\Resources\Products\Tables\ProductsTable::SELECTION_BAR_CLASS;
    $actions = array_filter(
        \Livewire\Livewire::current()->getTable()->getToolbarActions(),
        fn ($action): bool => $action->isVisible() && str_contains($action->getExtraAttributes()['class'] ?? '', $selectionBarClass),
    );
@endphp

<style>
    .fi-ta-header-toolbar .{{ $selectionBarClass }} {
        display: none !important;
    }

    /* On a phone the links take a second line instead of squeezing into one */
    .fi-ta-selection-indicator > div:last-child {
        flex-wrap: wrap;
        row-gap: 0.5rem;
    }

    /* Wider screens keep the links on the right, like Filament's own, even on a second line */
    @media (min-width: 640px) {
        .fi-ta-selection-indicator > div:last-child {
            justify-content: flex-end;
        }
    }

    .fi-ta-selection-indicator .fi-link,
    .fi-ta-selection-indicator > div:first-child {
        white-space: nowrap;
    }

    /* "Selecciona todos" and "Deselecciona todos" may each take their own line on a narrow phone */
    .fi-ta-selection-indicator-actions-ctn {
        display: contents;
    }

    .hade-selection-bar-actions {
        display: flex;
        column-gap: 0.75rem;
    }

    /*
     * On a phone the links were so close together that a finger could hit the wrong one:
     * each gets a 44px-tall tap area and clear space beside it.
     */
    @media (max-width: 639px) {
        .fi-ta-selection-indicator > div:last-child,
        .hade-selection-bar-actions {
            column-gap: 1.75rem;
            row-gap: 0;
        }

        .fi-ta-selection-indicator > div:last-child .fi-link {
            min-height: 2.75rem;
            align-items: center;
        }

        /* Filament lines the label up by its baseline, which drops the icon below the word */
        .fi-ta-selection-indicator > div:last-child .fi-link-label {
            align-self: center;
        }
    }
</style>

<div class="hade-selection-bar-actions">
    @foreach ($actions as $action)
        {{ $action }}
    @endforeach
</div>
