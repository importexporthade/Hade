<?php

namespace App\Filament\Widgets;

use App\Support\StoreMetrics;

/**
 * Where customers drop off: of the visitors who looked at a product, how many put one in
 * the cart, and how many sent the order.
 */
class PurchaseFunnelChart extends StoreChart
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Embudo de compra';

    protected ?string $description = 'Visitantes en cada paso, y qué parte de las que vieron productos llegó hasta ahí.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $funnel = StoreMetrics::funnel($this->days());

        return [
            'datasets' => [[
                'label' => 'Visitantes',
                'data' => array_values($funnel),
                'borderRadius' => 4,
                'maxBarThickness' => 36,
            ]],
            'labels' => $this->stepLabels($funnel),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $options['indexAxis'] = 'y';
        $options['interaction'] = ['mode' => 'nearest', 'intersect' => true];
        $options['scales'] = [
            'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0], 'border' => ['display' => false]],
            'y' => ['grid' => ['display' => false]],
        ];

        return $options;
    }

    public function getChartAssistiveContent(): string
    {
        return 'Embudo de compra: '.implode(', ', $this->stepLabels(StoreMetrics::funnel($this->days()))).'.';
    }

    /**
     * Each step with its share of the visitors who looked at products, so the drop-off is
     * read without doing the math.
     *
     * @param  array{viewed: int, added: int, ordered: int}  $funnel
     * @return list<string>
     */
    private function stepLabels(array $funnel): array
    {
        $share = fn (int $count): string => $funnel['viewed'] > 0 ? ' ('.round($count / $funnel['viewed'] * 100).' %)' : '';

        return [
            "Vieron productos: {$funnel['viewed']}",
            "Agregaron al carrito: {$funnel['added']}".$share($funnel['added']),
            "Enviaron pedido: {$funnel['ordered']}".$share($funnel['ordered']),
        ];
    }
}
