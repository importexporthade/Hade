<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Models\Banner;
use App\Support\ImageOptimizer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['linkProduct', 'linkCategory', 'linkPage']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagen')
                    ->state(fn (Banner $record): string => ImageOptimizer::displayPath($record->image, 640))
                    ->disk('public')
                    ->imageHeight(56),
                TextColumn::make('title')
                    ->label('Título')
                    ->placeholder('Sin título')
                    ->description(fn (Banner $record): ?string => $record->subtitle)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('destination')
                    ->label('Lleva a')
                    ->state(fn (Banner $record): string => $record->destinationLabel())
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->state(fn (Banner $record): string => match (true) {
                        ! $record->is_active => 'Apagado',
                        $record->starts_at?->isFuture() ?? false => 'Programado',
                        $record->ends_at?->isPast() ?? false => 'Vencido',
                        default => 'Visible',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Visible' => 'success',
                        'Programado' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('ends_at')
                    ->label('Hasta')
                    ->dateTime()
                    ->placeholder('Sin fecha')
                    ->wrap(),
                ToggleColumn::make('is_active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
