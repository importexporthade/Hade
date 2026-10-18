<?php

namespace App\Filament\Support;

use Carbon\CarbonImmutable;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * A date and time chosen the way people think about them: a day on a calendar and an hour
 * from a 12-hour list, instead of one box that mixes both with a 0–23 hour. The two are
 * joined into the model's datetime attribute when the form is saved.
 */
class DateTimeField
{
    public const string START_OF_DAY = '00:00';

    public const string END_OF_DAY = '23:59';

    /**
     * @param  string|null  $after  Name of another DateTimeField this one must come after.
     */
    public static function make(string $name, string $label, string $defaultTime, ?string $placeholder = null, ?string $after = null): Group
    {
        $date = self::dateField($name);
        $time = self::timeField($name);

        return Group::make([
            Grid::make(['default' => 3])->schema([
                DatePicker::make($date)
                    ->label($label)
                    ->placeholder($placeholder)
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->firstDayOfWeek(1)
                    ->closeOnDateSelection()
                    ->prefixIcon(Heroicon::OutlinedCalendarDays)
                    ->suffixActions([
                        Action::make("{$name}Today")
                            ->icon(Heroicon::OutlinedSun)
                            ->tooltip('Hoy')
                            ->action(fn (Set $set) => $set($date, now()->toDateString())),
                        Action::make("{$name}Clear")
                            ->icon(Heroicon::OutlinedXMark)
                            ->tooltip('Quitar fecha')
                            ->action(fn (Set $set) => $set($date, null)),
                    ])
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(fn (DatePicker $component, ?Model $record) => $component->state($record?->{$name}?->toDateString()))
                    ->rules($after === null ? [] : [fn (Get $get): Closure => self::mustComeAfter($get, $after, $name)])
                    ->columnSpan(2),
                Select::make($time)
                    ->label('Hora')
                    ->options(fn (?string $state): array => self::timeOptions($state))
                    ->default($defaultTime)
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->live()
                    ->dehydrated(false)
                    ->disabled(fn (Get $get): bool => blank($get($date)))
                    ->afterStateHydrated(fn (Select $component, ?Model $record) => $component->state($record?->{$name}?->format('H:i') ?? $defaultTime)),
            ]),
            Hidden::make($name)
                ->dehydrateStateUsing(fn (Get $get): ?string => self::combine($get($date), $get($time))?->toDateTimeString()),
        ]);
    }

    /**
     * Plain-words summary of a window between two of these fields, for under the form.
     */
    public static function summary(Get $get, string $start, string $end): string
    {
        $from = self::combine($get(self::dateField($start)), $get(self::timeField($start)));
        $until = self::combine($get(self::dateField($end)), $get(self::timeField($end)));

        $sentence = match (true) {
            $from === null && $until === null => 'Se verá desde que lo guardes y sin fecha de fin',
            $until === null => 'Se verá desde el '.self::readable($from).', sin fecha de fin',
            $from === null => 'Se verá desde que lo guardes hasta el '.self::readable($until),
            default => 'Se verá del '.self::readable($from).' hasta el '.self::readable($until),
        };

        // "p. m." already ends in a period
        return rtrim($sentence, '.').'.';
    }

    public static function combine(?string $date, ?string $time): ?CarbonImmutable
    {
        if (blank($date)) {
            return null;
        }

        return CarbonImmutable::parse(substr($date, 0, 10).' '.($time ?: self::START_OF_DAY));
    }

    /**
     * Every half hour of the day in 12-hour time, plus the last minute for "until the end of
     * the day", and the saved time if it falls in between.
     *
     * @return array<string, string>
     */
    public static function timeOptions(?string $current = null): array
    {
        $times = collect(range(0, 47))
            ->map(fn (int $halfHour): string => sprintf('%02d:%02d', intdiv($halfHour, 2), $halfHour % 2 * 30))
            ->push(self::END_OF_DAY);

        if (filled($current) && preg_match('/^\d{2}:\d{2}$/', $current)) {
            $times->push($current);
        }

        return $times->unique()->sort()->mapWithKeys(fn (string $time): array => [$time => self::twelveHour($time)])->all();
    }

    public static function twelveHour(string $time): string
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $suffix = $hour < 12 ? 'a. m.' : 'p. m.';
        $label = sprintf('%d:%02d %s', $hour % 12 === 0 ? 12 : $hour % 12, $minute, $suffix);

        return $time === self::END_OF_DAY ? "{$label} (fin del día)" : $label;
    }

    private static function readable(CarbonImmutable $moment): string
    {
        return $moment->format('d/m/Y').', '.str_replace(' (fin del día)', '', self::twelveHour($moment->format('H:i')));
    }

    private static function mustComeAfter(Get $get, string $start, string $end): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $start, $end): void {
            $from = self::combine($get(self::dateField($start)), $get(self::timeField($start)));
            $until = self::combine($get(self::dateField($end)), $get(self::timeField($end)));

            if ($from !== null && $until !== null && $until->lessThanOrEqualTo($from)) {
                $fail('La fecha de fin debe ser posterior a la de inicio.');
            }
        };
    }

    private static function dateField(string $name): string
    {
        return "{$name}_date";
    }

    private static function timeField(string $name): string
    {
        return "{$name}_time";
    }
}
