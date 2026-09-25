<?php

namespace App\Data\Reports;

use App\Support\Reports\Report;
use Spatie\LaravelData\Data;

/**
 * A report on the reports list.
 */
class ReportSummaryData extends Data
{
    public function __construct(
        public string $key,
        public string $title,
        public string $question,
    ) {}

    public static function fromReport(Report $report): self
    {
        return new self(key: $report->key(), title: $report->title(), question: $report->question());
    }
}
