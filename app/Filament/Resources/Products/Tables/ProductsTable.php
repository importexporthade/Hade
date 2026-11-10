<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Product;
use App\Support\ImageOptimizer;
use App\Support\Price;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
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
    /**
     * Marks the bulk actions that move from the toolbar to the selection bar.
     */
    public const string SELECTION_BAR_CLASS = 'hade-selection-bar-action';

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('category'))
            ->defaultSort('sort_order')
            ->description('Los interruptores se guardan solos al tocarlos. Para cambiar precio, fotos o textos, pulsa Editar. Para poner o quitar ofertas, o borrar varios a la vez, marca sus casillas.')
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
                    ->description(fn (Product $record): ?string => $record->category
                        ? $record->category->name.($record->category->is_visible ? '' : ' · categoría oculta')
                        : null)
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
                    ->color('danger')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        'case when compare_at_price > price then (compare_at_price - price) * 1.0 / compare_at_price else 0 end '.($direction === 'desc' ? 'desc' : 'asc'),
                    )),
                ToggleColumn::make('is_visible')
                    ->label('Visible')
                    ->afterStateUpdated(function (Product $record, bool $state): void {
                        if (! $state || $record->isShownInStore()) {
                            return;
                        }

                        Notification::make()
                            ->title('Su categoría está oculta')
                            ->body("«{$record->name}» está encendido, pero no aparecerá en la tienda hasta que muestres la categoría «{$record->category->name}».")
                            ->warning()
                            ->persistent()
                            ->actions([
                                Action::make('categories')
                                    ->label('Ir a Categorías')
                                    ->url(CategoryResource::getUrl('index')),
                            ])
                            ->send();
                    }),
                ToggleColumn::make('is_in_stock')
                    ->label('Stock'),
                ToggleColumn::make('is_featured')
                    ->label('Novedad'),
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
                Filter::make('marked_as_new')
                    ->label('Novedades')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
                TernaryFilter::make('is_visible')
                    ->label('Visible en la tienda'),
                TernaryFilter::make('is_in_stock')
                    ->label('Con stock'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            // Shown as two small links beside "Deselecciona todos" once a product is ticked
            // (see the selection-bar view), instead of hiding behind a "⋮" menu.
            ->toolbarActions([
                BulkAction::make('offer')
                    ->label('Oferta')
                    ->icon(Heroicon::OutlinedTag)
                    ->link()
                    ->extraAttributes(['class' => self::SELECTION_BAR_CLASS], merge: true)
                    ->modalHeading(fn (Collection $records): string => $records->count() === 1 ? 'Oferta de 1 producto' : "Oferta de {$records->count()} productos")
                    ->modalSubmitActionLabel('Guardar')
                    ->schema([
                        Toggle::make('on_sale')
                            ->label('En oferta')
                            ->helperText('Apágalo para que vuelvan a su precio normal.')
                            ->default(true)
                            ->live(),
                        TextInput::make('percentage')
                            ->label('Descuento')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Cuánto le bajas al precio normal. Ej: con 20, S/ 50.00 queda en S/ 40.00.')
                            ->helperText('Se calcula sobre el precio normal de cada producto.')
                            ->visible(fn (Get $get): bool => (bool) $get('on_sale'))
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(90)
                            ->suffix('%'),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        if (! $data['on_sale']) {
                            $records->each(fn (Product $product) => $product->removeDiscount());

                            Notification::make()->success()->title('Ofertas quitadas')->send();

                            return;
                        }

                        $records->each(fn (Product $product) => $product->applyDiscount((int) $data['percentage']));

                        Notification::make()
                            ->success()
                            ->title("{$records->count()} productos en oferta con -{$data['percentage']} %")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make()
                    ->label('Borrar')
                    ->link()
                    ->extraAttributes(['class' => self::SELECTION_BAR_CLASS], merge: true),
            ]);
    }
}
