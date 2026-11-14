<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PageVisibility;
use App\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->description('Elige dónde se ve cada página; el cambio se guarda solo.')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->description(fn (Page $record): string => '/'.$record->slug)
                    ->wrap()
                    ->searchable(),
                SelectColumn::make('visibility')
                    ->label('Dónde se ve')
                    ->options(PageVisibility::class)
                    ->selectablePlaceholder(false),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->since(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
