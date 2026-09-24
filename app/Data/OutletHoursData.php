<?php

namespace App\Data;

use App\Models\Outlet;
use App\Models\OutletDateException;
use App\Support\OpeningHours;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * An outlet's weekly hours and upcoming dates with different hours. Load `dateExceptions` first.
 */
#[MapName(SnakeCaseMapper::class)]
class OutletHoursData extends Data
{
    /**
     * @param  array<int, list<OpeningPeriodData>>  $regularHours  Keyed by ISO weekday, 1 for Monday; every day present.
     * @param  list<DateExceptionData>  $dateExceptions
     */
    public function __construct(
        #[LiteralTypeScriptType('Record<string, App.Data.OpeningPeriodData[]>')]
        public array $regularHours,
        public array $dateExceptions,
        public string $timezone,
    ) {}

    public static function fromModel(Outlet $outlet, string $fromDate): self
    {
        $regularHours = [];

        foreach (OpeningHours::DAYS as $day) {
            $regularHours[$day] = OpeningPeriodData::list($outlet->regular_hours[$day] ?? []);
        }

        return new self(
            regularHours: $regularHours,
            dateExceptions: array_values($outlet->dateExceptions
                ->filter(fn (OutletDateException $exception): bool => $exception->date->toDateString() >= $fromDate)
                ->map(fn (OutletDateException $exception): DateExceptionData => DateExceptionData::fromModel($exception))
                ->all()),
            timezone: $outlet->timezone,
        );
    }
}
