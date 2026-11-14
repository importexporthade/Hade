<?php

namespace App\Filament\Resources\Complaints\Tables;

use App\Enums\ComplaintType;
use App\Models\Complaint;
use App\Support\ComplaintSheetPdf;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aún no hay hojas de reclamación')
            ->emptyStateDescription('Cuando una clienta llene el Libro de Reclamaciones en la tienda, aparecerá aquí.')
            ->columns([
                TextColumn::make('sequence')
                    ->label('N°')
                    ->formatStateUsing(fn (Complaint $record): string => $record->code())
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('consumer_name')
                    ->label('Clienta')
                    ->description(fn (Complaint $record): string => $record->email)
                    ->wrap()
                    ->searchable(['consumer_name', 'email', 'document_number']),
                TextColumn::make('item_description')
                    ->label('Sobre')
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->state(fn (Complaint $record): string => match (true) {
                        $record->isAnswered() => 'Respondida',
                        $record->responseDueDate()->isPast() => 'Vencida',
                        default => 'Por responder',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Respondida' => 'success',
                        'Vencida' => 'danger',
                        default => 'warning',
                    })
                    ->description(fn (Complaint $record): ?string => $record->isAnswered() ? null : 'Hasta el '.$record->responseDueDate()->format('d/m/Y')),
            ])
            ->filters([
                TernaryFilter::make('answered')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Respondidas')
                    ->falseLabel('Por responder')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('responded_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('responded_at'),
                    ),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(ComplaintType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (Complaint $record): StreamedResponse => ComplaintSheetPdf::download($record)),
            ]);
    }
}
