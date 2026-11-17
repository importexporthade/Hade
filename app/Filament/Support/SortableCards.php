<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Tables\Table;

/**
 * A table the owner arranges by dragging, always in reorder mode: a plain click on a row
 * opens its edit window, and on a phone a row is picked up only after holding it. The page
 * keeps reorder mode on with the AlwaysReordering trait.
 */
class SortableCards
{
    /**
     * On a phone the whole card is a drag handle, so a finger only picks a row up after
     * holding it for a moment; until then the list scrolls as usual.
     */
    private const string HOLD_TO_DRAG = <<<'JS'
        const list = $event.target.closest('[x-sortable]');

        if (list?.sortable) {
            list.sortable.option('delay', 300);
            list.sortable.option('delayOnTouchOnly', true);
        }
        JS;

    /**
     * A plain click on a row opens its edit window. Its own switch and buttons keep doing
     * their job, and a drag never ends in a click.
     */
    private const string CLICK_TO_EDIT = <<<'JS'
        const row = $event.target.closest('[x-sortable-item]');

        if (row && ! $event.target.closest('button, a, input, label, select, textarea, [role=switch]')) {
            $wire.mountTableAction('edit', row.getAttribute('x-sortable-item'));
        }
        JS;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            // The table always stays in reorder mode, so the button that leaves it is hidden.
            // Filament prints it even when hidden(), hence the theme class.
            ->reorderRecordsTriggerAction(fn (Action $action): Action => $action->extraAttributes(['class' => 'hade-always-reordering'], merge: true))
            ->extraAttributes([
                'class' => 'hade-click-to-edit',
                'x-on:pointerdown.capture' => self::HOLD_TO_DRAG,
                'x-on:click' => self::CLICK_TO_EDIT,
            ], merge: true);
    }
}
