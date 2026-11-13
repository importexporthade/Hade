<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Banner;
use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = StoreActivity::POLLING_INTERVAL;

    /**
     * Two cards per row until there is room for four that still fit their names.
     *
     * @var array<string, int>
     */
    protected int|array|null $columns = StoreActivity::COLUMNS;

    protected function getStats(): array
    {
        return [
            Stat::make('Productos en la tienda', Product::query()->visible()->count())
                ->description(Product::query()->where('is_visible', false)->count().' ocultos')
                ->icon(Heroicon::OutlinedShoppingBag)
                ->url(ProductResource::getUrl()),
            Stat::make('En oferta', Product::query()->visible()->onSale()->count())
                ->icon(Heroicon::OutlinedTag)
                ->color('danger'),
            Stat::make('Sin stock', Product::query()->visible()->where('is_in_stock', false)->count())
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning'),
            Stat::make('Banners visibles', Banner::query()->showing()->count())
                ->icon(Heroicon::OutlinedPhoto),
        ];
    }
}
