<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Support\WithoutBreadcrumbs;
use App\Support\ComplaintBookExport;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListComplaints extends ListRecords
{
    use WithoutBreadcrumbs;

    protected static string $resource = ComplaintResource::class;

    protected static ?string $title = 'Libro de Reclamaciones';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar a Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->modalHeading('Exportar el Libro de Reclamaciones')
                ->modalDescription('Descarga las hojas registradas entre dos fechas, con sus respuestas, en un archivo que se abre en Excel.')
                ->modalSubmitActionLabel('Descargar')
                ->modalSubmitAction(fn (Action $action): Action => $action->icon(Heroicon::OutlinedArrowDownTray))
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->default(now()->startOfYear())
                            ->maxDate(now())
                            ->required(),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->default(now())
                            ->afterOrEqual('from')
                            ->validationMessages(['after_or_equal' => 'La fecha «Hasta» debe ser igual o posterior a «Desde».'])
                            ->required(),
                    ]),
                ])
                ->action(fn (array $data): StreamedResponse => ComplaintBookExport::download(
                    Carbon::parse($data['from']),
                    Carbon::parse($data['until']),
                )),
        ];
    }
}
