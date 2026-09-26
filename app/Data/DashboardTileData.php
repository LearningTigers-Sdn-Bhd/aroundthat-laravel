<?php

namespace App\Data;

use App\Enums\ReportValueFormat;
use App\Support\Reports\ReportTile;
use Spatie\LaravelData\Data;

/**
 * A headline number on the dashboard, taken from a report and linking to it.
 */
class DashboardTileData extends Data
{
    public function __construct(
        public string $label,
        public int|float|string|null $value,
        public ReportValueFormat $format,
        public ?string $hint,
        public string $report,
    ) {}

    public static function fromTile(ReportTile $tile, string $report): self
    {
        return new self(label: $tile->label, value: $tile->value, format: $tile->format, hint: $tile->hint, report: $report);
    }
}
