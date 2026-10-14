<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->description(fn (Page $record): string => '/'.$record->slug)
                    ->searchable(),
                ToggleColumn::make('is_published')
                    ->label('Publicada'),
                ToggleColumn::make('show_in_footer')
                    ->label('En el pie'),
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
