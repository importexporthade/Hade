<?php

namespace App\Filament\Widgets;

use App\Enums\ProductEventType;
use App\Support\StoreMetrics;

/**
 * How many product pages customers opened each day.
 */
class ProductViewsChart extends StoreChart
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Visitas a productos por día';

    protected ?string $description = 'Cuántas veces las clientas abrieron la página de un producto.';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $views = StoreMetrics::dailyEvents(ProductEventType::View, $this->days());

        return [
            'datasets' => [[
                'label' => 'Visitas',
                'data' => array_values($views),
                'fill' => true,
                'tension' => 0.3,
                'cubicInterpolationMode' => 'monotone',
                'borderWidth' => 2,
                'pointRadius' => 0,
                'pointHoverRadius' => 5,
            ]],
            'labels' => StoreMetrics::dayLabels($views),
        ];
    }

    public function getChartAssistiveContent(): string
    {
        $views = StoreMetrics::dailyEvents(ProductEventType::View, $this->days());

        return 'Visitas por día: '.collect($views)->map(fn (int $count, string $day): string => "{$day}: {$count}")->implode(', ').'.';
    }
}
