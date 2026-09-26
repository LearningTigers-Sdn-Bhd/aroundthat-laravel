<?php

namespace App\Support\Reports;

use App\Enums\ReportValueFormat;
use Spatie\LaravelData\Data;

/**
 * One headline number above a report table. A null value means it is hidden for privacy or has nothing to show.
 */
class ReportTile extends Data
{
    public function __construct(
        public string $label,
        public int|float|string|null $value,
        public ReportValueFormat $format,
        public ?string $hint = null,
    ) {}
}
