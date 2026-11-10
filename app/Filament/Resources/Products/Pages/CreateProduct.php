<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Filament scrolls to the first field with a problem; the cursor now goes into it too,
     * so the owner can fix it right away.
     */
    protected function onValidationError(ValidationException $exception): void
    {
        parent::onValidationError($exception);

        $this->js(<<<'JS'
            setTimeout(() => document.querySelector('[data-validation-error]')
                ?.closest('[data-field-wrapper]')
                ?.querySelector('input:not([type=hidden]), textarea, [contenteditable=true]')
                ?.focus({ preventScroll: true }), 300);
        JS);
    }
}
