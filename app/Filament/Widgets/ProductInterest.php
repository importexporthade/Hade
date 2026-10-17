<?php

namespace App\Filament\Widgets;

use App\Enums\ProductEventType;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\ImageOptimizer;
use Closure;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The products customers looked at, put in the cart and ordered most over the last 30 days.
 */
class ProductInterest extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $since = now()->subDays(StoreActivity::DAYS)->startOfDay();
        $recentOfType = fn (ProductEventType $type): Closure => fn (Builder $query): Builder => $query
            ->where('type', $type->value)
            ->where('created_at', '>=', $since);

        return $table
            ->heading('Productos con más interés (últimos 30 días)')
            ->query(fn (): Builder => Product::query()
                ->whereHas('events', fn (Builder $query): Builder => $query->where('created_at', '>=', $since))
                ->withCount(['events as views_count' => $recentOfType(ProductEventType::View)])
                ->withSum(['events as added_quantity' => $recentOfType(ProductEventType::AddToCart)], 'quantity')
                ->withSum(['events as ordered_quantity' => $recentOfType(ProductEventType::Checkout)], 'quantity'))
            ->defaultSort('views_count', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Aún no hay actividad')
            ->emptyStateDescription('Cuando las clientas vean productos o los agreguen al carrito, aparecerán aquí.')
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->state(fn (Product $record): ?string => $record->coverPath() ? ImageOptimizer::displayPath($record->coverPath(), 320) : null)
                    ->disk('public')
                    ->defaultImageUrl(asset('images/product-placeholder.svg'))
                    ->square(),
                TextColumn::make('name')
                    ->label('Producto'),
                TextColumn::make('views_count')
                    ->label('Vistas')
                    ->sortable(),
                TextColumn::make('added_quantity')
                    ->label('Al carrito')
                    ->default(0)
                    ->sortable(),
                TextColumn::make('ordered_quantity')
                    ->label('Pedidos')
                    ->default(0)
                    ->sortable(),
            ]);
    }
}
