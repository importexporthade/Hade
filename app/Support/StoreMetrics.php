<?php

namespace App\Support;

use App\Enums\ProductEventType;
use App\Models\Checkout;
use App\Models\ProductEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Day-by-day figures for the manager's charts, with every day of the period present (days
 * without activity count as zero, so lines do not skip them).
 */
class StoreMetrics
{
    /**
     * Periods the charts offer, in days, with the label of each.
     *
     * @var array<string, string>
     */
    public const array PERIODS = [
        '7' => 'Últimos 7 días',
        '30' => 'Últimos 30 días',
        '90' => 'Últimos 90 días',
    ];

    public const int DEFAULT_DAYS = 30;

    public static function days(?string $period): int
    {
        return array_key_exists((string) $period, self::PERIODS) ? (int) $period : self::DEFAULT_DAYS;
    }

    public static function since(int $days): CarbonImmutable
    {
        return now()->toImmutable()->subDays($days - 1)->startOfDay();
    }

    /**
     * Number of events of one type on each day.
     *
     * @return array<string, int> keyed by Y-m-d
     */
    public static function dailyEvents(ProductEventType $type, int $days): array
    {
        return self::fillDays(
            ProductEvent::query()->ofType($type)->where('created_at', '>=', self::since($days)),
            $days,
        );
    }

    /**
     * Number of orders sent to WhatsApp on each day.
     *
     * @return array<string, int> keyed by Y-m-d
     */
    public static function dailyCheckouts(int $days): array
    {
        return self::fillDays(Checkout::query()->where('created_at', '>=', self::since($days)), $days);
    }

    /**
     * Distinct visitors at each step from looking at a product to sending an order.
     *
     * @return array{viewed: int, added: int, ordered: int}
     */
    public static function funnel(int $days): array
    {
        $since = self::since($days);
        $visitors = fn (ProductEventType $type): int => ProductEvent::query()
            ->ofType($type)
            ->where('created_at', '>=', $since)
            ->whereNotNull('visitor_id')
            ->distinct()
            ->count('visitor_id');

        return [
            'viewed' => $visitors(ProductEventType::View),
            'added' => $visitors(ProductEventType::AddToCart),
            'ordered' => Checkout::query()->where('created_at', '>=', $since)->whereNotNull('visitor_id')->distinct()->count('visitor_id'),
        ];
    }

    /**
     * Short day labels for the x axis: "17/10".
     *
     * @param  array<string, int>  $series
     * @return list<string>
     */
    public static function dayLabels(array $series): array
    {
        return array_map(fn (string $day): string => CarbonImmutable::parse($day)->format('d/m'), array_keys($series));
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, int>
     */
    private static function fillDays(Builder $query, int $days): array
    {
        $counts = $query
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->mapWithKeys(fn (mixed $total, string $day): array => [substr($day, 0, 10) => (int) $total]);

        $series = [];

        for ($day = self::since($days); $day->lessThanOrEqualTo(now()); $day = $day->addDay()) {
            $series[$day->toDateString()] = $counts[$day->toDateString()] ?? 0;
        }

        return $series;
    }
}
