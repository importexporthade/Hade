<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Support\WithoutBreadcrumbs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    use WithoutBreadcrumbs;

    protected static string $resource = PageResource::class;

    protected static ?string $title = 'Páginas informativas';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
