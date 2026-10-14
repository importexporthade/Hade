<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHomeSections extends ManageRecords
{
    protected static string $resource = HomeSectionResource::class;

    protected static ?string $title = 'Secciones de portada';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
