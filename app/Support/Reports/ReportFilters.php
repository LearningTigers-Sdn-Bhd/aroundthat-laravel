<?php

namespace App\Support\Reports;

use App\Enums\ReportGrouping;

/**
 * One reading of a report: whose data, which days, what each row stands for, and any narrowing.
 */
final readonly class ReportFilters
{
    public function __construct(
        public ReportScope $scope,
        public ReportPeriod $period,
        public ReportGrouping $grouping,
        public ?string $offerId = null,
        public ?string $outletId = null,
    ) {}

    /**
     * The outlets to read: the chosen one, or every outlet in the scope.
     *
     * @return list<string>
     */
    public function outletIds(): array
    {
        return $this->outletId === null ? $this->scope->outletIds : [$this->outletId];
    }
}
