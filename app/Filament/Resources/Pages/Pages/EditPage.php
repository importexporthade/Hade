<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\BreadcrumbsBackToTheList;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use BreadcrumbsBackToTheList;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * A page is edited once and left alone, so saving goes back to the list of pages.
     */
    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceUrl();
    }
}
