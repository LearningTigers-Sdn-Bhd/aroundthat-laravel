<?php

namespace App\Data\Reports;

use App\Enums\ReportFilter;
use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use App\Support\Reports\Report;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportTile;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One report as the shared report page shows it: the toolbar's choices, the tiles and the table.
 * `offerOptions` and `outletOptions` are null when the report does not accept that filter.
 */
#[MapName(SnakeCaseMapper::class)]
class ReportPageData extends Data
{
    /**
     * @param  list<ReportOptionData>  $periodOptions
     * @param  list<ReportOptionData>  $groupingOptions
     * @param  list<ReportOptionData>|null  $offerOptions
     * @param  list<ReportOptionData>|null  $outletOptions
     * @param  list<ReportTile>  $tiles
     * @param  list<ReportColumn>  $columns
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $question,
        public ReportPeriodPreset $period,
        public string $from,
        public string $to,
        public string $periodLabel,
        public array $periodOptions,
        public ReportGrouping $grouping,
        public array $groupingOptions,
        public ?string $offer,
        public ?array $offerOptions,
        public ?string $outlet,
        public ?array $outletOptions,
        public string $currency,
        public array $tiles,
        public array $columns,
        public array $rows,
    ) {}

    public static function fromReport(Report $report, ReportFilters $filters): self
    {
        $accepts = fn (ReportFilter $filter): bool => in_array($filter, $report->filters(), true);

        return new self(
            key: $report->key(),
            title: $report->title(),
            question: $report->question(),
            period: $filters->period->preset,
            from: $filters->period->from->toDateString(),
            to: $filters->period->to->toDateString(),
            periodLabel: $filters->period->label(),
            periodOptions: array_map(fn (ReportPeriodPreset $preset) => new ReportOptionData($preset->value, $preset->label()), ReportPeriodPreset::cases()),
            grouping: $filters->grouping,
            groupingOptions: array_map(fn (ReportGrouping $grouping) => new ReportOptionData($grouping->value, $grouping->label()), $report->groupings()),
            offer: $filters->offerId,
            offerOptions: $accepts(ReportFilter::Offer) ? array_values($filters->scope->business->voucherOffers()->orderBy('name')->get(['id', 'name'])
                ->map(fn (VoucherOffer $offer) => new ReportOptionData($offer->id, $offer->name))->all()) : null,
            outlet: $filters->outletId,
            outletOptions: $accepts(ReportFilter::Outlet) ? array_values(Outlet::query()->whereKey($filters->scope->outletIds)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Outlet $outlet) => new ReportOptionData($outlet->id, $outlet->name))->all()) : null,
            currency: config('vouchers.currency'),
            tiles: $report->summary($filters),
            columns: $report->columns($filters),
            rows: $report->rows($filters),
        );
    }
}
