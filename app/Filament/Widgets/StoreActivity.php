<?php

namespace App\Filament\Widgets;

use App\Enums\ProductEventType;
use App\Models\Checkout;
use App\Models\ProductEvent;
use App\Support\Price;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What customers did in the store over the last 30 days: from looking at products to
 * sending an order through WhatsApp.
 */
class StoreActivity extends StatsOverviewWidget
{
    public const int DAYS = 30;

    /**
     * Two cards per row until there is room for four that still fit their names, such as
     * "Pedidos por WhatsApp". Filament's default puts four in a box only 576px wide.
     *
     * @var array<string, int>
     */
    public const array COLUMNS = ['default' => 2, '@3xl' => 4, '!@lg' => 4];

    protected static ?int $sort = 0;

    /**
     * @var array<string, int>
     */
    protected int|array|null $columns = self::COLUMNS;

    protected ?string $heading = 'Actividad de los últimos 30 días';

    protected function getStats(): array
    {
        $views = ProductEvent::query()->ofType(ProductEventType::View)->inLastDays(self::DAYS);
        $visitors = (clone $views)->distinct()->count('visitor_id');
        $checkouts = Checkout::query()->where('created_at', '>=', now()->subDays(self::DAYS)->startOfDay());
        $buyers = (clone $checkouts)->distinct()->count('visitor_id');

        return [
            Stat::make('Productos vistos', (clone $views)->count())
                ->description("{$visitors} ".($visitors === 1 ? 'visitante' : 'visitantes'))
                ->icon(Heroicon::OutlinedEye),
            Stat::make('Agregados al carrito', (int) ProductEvent::query()->ofType(ProductEventType::AddToCart)->inLastDays(self::DAYS)->sum('quantity'))
                ->icon(Heroicon::OutlinedShoppingCart),
            Stat::make('Pedidos por WhatsApp', (clone $checkouts)->count())
                ->description(Price::format((float) (clone $checkouts)->sum('total')).' en pedidos')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('success'),
            Stat::make('Conversión', $visitors > 0 ? round($buyers / $visitors * 100, 1).' %' : '—')
                ->description('Visitantes que enviaron un pedido')
                ->icon(Heroicon::OutlinedArrowTrendingUp),
        ];
    }
}
