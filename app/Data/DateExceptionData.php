<?php

namespace App\Data;

use App\Models\OutletDateException;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A date an outlet keeps different hours. Periods are empty on a closed date.
 */
#[MapName(SnakeCaseMapper::class)]
class DateExceptionData extends Data
{
    /**
     * @param  list<OpeningPeriodData>  $periods
     */
    public function __construct(
        public string $date,
        public bool $isClosed,
        public array $periods,
        public ?string $note,
    ) {}

    public static function fromModel(OutletDateException $exception): self
    {
        return new self(
            date: $exception->date->toDateString(),
            isClosed: $exception->is_closed,
            periods: OpeningPeriodData::list($exception->periods ?? []),
            note: $exception->note,
        );
    }
}
