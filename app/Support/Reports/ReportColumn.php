<?php

namespace App\Support\Reports;

use App\Enums\ReportValueFormat;
use Spatie\LaravelData\Data;

/**
 * One column of a report table and of its CSV. `key` names the value in each row.
 */
class ReportColumn extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public ReportValueFormat $format,
    ) {}
}
