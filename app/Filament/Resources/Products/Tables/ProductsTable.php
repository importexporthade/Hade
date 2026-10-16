<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Support\ImageOptimizer;
use App\Support\Price;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('category'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('cover')
                    ->label('Foto')
                    ->state(fn (Product $record): ?string => $record->coverPath() ? ImageOptimizer::displayPath($record->coverPath(), 320) : null)
                    ->disk('public')
                    ->defaultImageUrl(asset('images/product-placeholder.svg'))
                    ->square(),
                TextColumn::make('name')
                    ->label('Producto')
                    ->description(fn (Product $record): ?string => $record->category?->name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->formatStateUsing(fn (string $state): ?string => Price::format($state))
                    ->description(fn (Product $record): ?string => $record->isOnSale() ? 'Antes '.Price::format($record->compare_at_price) : null)
                    ->sortable(),
                TextColumn::make('discount')
                    ->label('Oferta')
                    ->state(fn (Product $record): ?string => $record->isOnSale() ? "-{$record->discountPercentage()} %" : null)
                    ->badge()
                    ->color('danger'),
                ToggleColumn::make('is_visible')
                    ->label('Visible'),
                ToggleColumn::make('is_in_stock')
                    ->label('Stock'),
                ToggleColumn::make('is_featured')
                    ->label('Destacado'),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->preload(),
                Filter::make('on_sale')
                    ->label('En oferta')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->onSale()),
                TernaryFilter::make('is_visible')
                    ->label('Visible en la tienda'),
                TernaryFilter::make('is_in_stock')
                    ->label('Con stock'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('applyDiscount')
                        ->label('Poner en oferta')
                        ->icon(Heroicon::OutlinedTag)
                        ->schema([
                            TextInput::make('percentage')
                                ->label('Descuento')
                                ->helperText('Se calcula sobre el precio normal de cada producto.')
                                ->required()
                                ->integer()
                                ->minValue(1)
                                ->maxValue(90)
                                ->suffix('%'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (Product $product) => $product->applyDiscount((int) $data['percentage']));

                            Notification::make()
                                ->success()
                                ->title("{$records->count()} productos en oferta con -{$data['percentage']} %")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('removeDiscount')
                        ->label('Quitar oferta')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->requiresConfirmation()
                        ->modalDescription('Los productos vuelven a su precio normal.')
                        ->action(function (Collection $records): void {
                            $records->each(fn (Product $product) => $product->removeDiscount());

                            Notification::make()
                                ->success()
                                ->title('Ofertas quitadas')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
