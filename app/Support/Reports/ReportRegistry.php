<?php

namespace App\Support\Reports;

use App\Support\Reports\Types\RedemptionsReport;

/**
 * Every report a business can open, found by the key in its URL.
 */
final class ReportRegistry
{
    /**
     * In the order the reports list shows them.
     *
     * @var list<class-string<Report>>
     */
    protected const array REPORTS = [
        RedemptionsReport::class,
    ];

    /**
     * @return list<Report>
     */
    public function all(): array
    {
        return array_map(fn (string $report): Report => app($report), self::REPORTS);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(fn (Report $report): string => $report->key(), $this->all());
    }

    /**
     * Stop with a 404 when no report has the key.
     */
    public function find(string $key): Report
    {
        foreach ($this->all() as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        abort(404);
    }
}
