<?php

namespace App\Filament\Support;

/**
 * Opens a SortableCards table ready to drag: ordering is the main job of the page, so the
 * owner should not have to switch into a reorder mode first.
 */
trait AlwaysReordering
{
    public function mount(): void
    {
        parent::mount();

        $this->isTableReordering = true;
    }

    /**
     * Reorder mode stays on; the hidden trigger means nothing can switch it off.
     */
    public function toggleTableReordering(): void {}
}
