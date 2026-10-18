<?php

namespace App\Filament\Widgets;

use App\Support\StoreMetrics;

/**
 * How many orders customers sent to WhatsApp each day.
 */
class OrdersChart extends StoreChart
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Pedidos por WhatsApp por día';

    protected ?string $description = 'Pedidos enviados desde el carrito.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $orders = StoreMetrics::dailyCheckouts($this->days());

        return [
            'datasets' => [[
                'label' => 'Pedidos',
                'data' => array_values($orders),
                'borderRadius' => 4,
                'maxBarThickness' => 24,
            ]],
            'labels' => StoreMetrics::dayLabels($orders),
        ];
    }

    public function getChartAssistiveContent(): string
    {
        $orders = StoreMetrics::dailyCheckouts($this->days());

        return 'Pedidos por día: '.collect($orders)->map(fn (int $count, string $day): string => "{$day}: {$count}")->implode(', ').'.';
    }
}
