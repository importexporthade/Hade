<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use App\Filament\Support\BreadcrumbsBackToTheList;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHomeSections extends ManageRecords
{
    use BreadcrumbsBackToTheList;

    protected static string $resource = HomeSectionResource::class;

    protected static ?string $title = 'Secciones de portada';

    /**
     * The sections open ready to drag: ordering them is the main job of this page, so the
     * owner should not have to switch into a reorder mode first.
     */
    public function mount(): void
    {
        parent::mount();

        $this->isTableReordering = true;
    }

    /**
     * Reorder mode stays on; the hidden trigger means nothing can switch it off.
     */
    public function toggleTableReordering(): void {}

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
