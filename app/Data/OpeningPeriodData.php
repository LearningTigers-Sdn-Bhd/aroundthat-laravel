<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * One opening period in the outlet's local time. A closing time of 00:00 means the end of the day.
 */
class OpeningPeriodData extends Data
{
    public function __construct(
        public string $opens,
        public string $closes,
    ) {}

    /**
     * @param  list<array{opens: string, closes: string}>  $periods
     * @return list<self>
     */
    public static function list(array $periods): array
    {
        return array_map(fn (array $period): self => new self($period['opens'], $period['closes']), $periods);
    }
}
