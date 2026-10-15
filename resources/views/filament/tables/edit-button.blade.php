{{--
    Opens the row's edit window from a table that stays in reorder mode, where Filament
    turns off row clicks and row buttons.
--}}
<div class="px-3 py-4">
    <x-filament::link
        tag="button"
        type="button"
        icon="heroicon-m-pencil-square"
        wire:click="mountTableAction('edit', '{{ $getRecord()->getKey() }}')"
    >
        Editar
    </x-filament::link>
</div>
