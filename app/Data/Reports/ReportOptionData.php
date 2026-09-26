<?php

namespace App\Data\Reports;

use Spatie\LaravelData\Data;

/**
 * One choice in a report's toolbar: a period, a grouping, an offer or an outlet.
 */
class ReportOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
