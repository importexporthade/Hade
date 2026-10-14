<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Models\Banner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagen')
                    ->disk('public')
                    ->imageHeight(56),
                TextColumn::make('title')
                    ->label('Título')
                    ->placeholder('Sin título')
                    ->description(fn (Banner $record): ?string => $record->subtitle)
                    ->searchable(),
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
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin fecha'),
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
