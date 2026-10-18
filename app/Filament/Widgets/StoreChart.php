<?php

namespace App\Filament\Widgets;

use App\Support\StoreMetrics;
use Filament\Widgets\ChartWidget;

/**
 * Shared look of the dashboard charts: one series in the brand color, a period picker,
 * quiet axes, whole numbers, and no legend since the title already names the series.
 */
abstract class StoreChart extends ChartWidget
{
    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return StoreMetrics::PERIODS;
    }

    protected function days(): int
    {
        return StoreMetrics::days($this->filter);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'locale' => 'es-PE',
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 16],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                    'border' => ['display' => false],
                ],
            ],
        ];
    }
}
