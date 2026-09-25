<?php

namespace App\Support\Reports;

use App\Enums\ReportFilter;
use App\Enums\ReportGrouping;

/**
 * One report: a single question answered as a few tiles and a small table. The shared page, the CSV and the
 * dashboard read every report through this shape.
 *
 * Each row is keyed by column key, plus `key` (unique in the report) and `hidden` (true when the privacy guard
 * emptied its values).
 */
interface Report
{
    /** The URL name, such as `redemptions`. */
    public function key(): string;

    public function title(): string;

    /** The one question the report answers, in plain words. */
    public function question(): string;

    /**
     * What a row may stand for. The first one is the default.
     *
     * @return non-empty-list<ReportGrouping>
     */
    public function groupings(): array;

    /**
     * @return list<ReportFilter>
     */
    public function filters(): array;

    /**
     * @return list<ReportColumn>
     */
    public function columns(ReportFilters $filters): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(ReportFilters $filters): array;

    /**
     * @return list<ReportTile>
     */
    public function summary(ReportFilters $filters): array;
}
