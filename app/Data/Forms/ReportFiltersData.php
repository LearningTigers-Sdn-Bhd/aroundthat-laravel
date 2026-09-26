<?php

namespace App\Data\Forms;

use App\Enums\ReportFilter;
use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use App\Support\Reports\Report;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportScope;
use App\Support\Workspace;
use Closure;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * A report's query string: `period` (a preset, or `custom` with `from` and `to`), `group`, `offer` and `outlet`.
 * The offer must be the business's and the outlet one the member reaches, so another business's ids are refused
 * without saying whether they exist.
 */
class ReportFiltersData extends Data
{
    public function __construct(
        public ReportPeriodPreset $period = ReportPeriodPreset::ThisMonth,
        public ?string $from = null,
        public ?string $to = null,
        public ?ReportGrouping $group = null,
        public ?string $offer = null,
        public ?string $outlet = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $workspace = app(Workspace::class);

        return [
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', function (string $attribute, mixed $value, Closure $fail) use ($context, $workspace): void {
                $from = $context->payload['from'] ?? null;

                if (($context->payload['period'] ?? null) !== ReportPeriodPreset::Custom->value || ! is_string($from) || ! is_string($value)) {
                    return;
                }

                try {
                    ReportPeriod::custom($from, $value, $workspace->business()->timezone);
                } catch (InvalidArgumentException $problem) {
                    $fail($problem->getMessage());
                }
            }],
            'offer' => ['nullable', 'bail', 'uuid', Rule::exists('voucher_offers', 'id')->where('business_id', $workspace->business()->id)],
            'outlet' => ['nullable', 'bail', 'uuid', Rule::in(ReportScope::for($workspace->membership())->outletIds)],
        ];
    }

    /**
     * The filters for one report. A grouping or narrowing the report does not offer falls back to its default.
     */
    public function toFilters(Report $report, ReportScope $scope): ReportFilters
    {
        $timezone = $scope->business->timezone;

        $period = $this->period === ReportPeriodPreset::Custom
            ? ReportPeriod::custom((string) $this->from, (string) $this->to, $timezone)
            : ReportPeriod::preset($this->period, $timezone);

        return new ReportFilters(
            scope: $scope,
            period: $period,
            grouping: in_array($this->group, $report->groupings(), true) ? $this->group : $report->groupings()[0],
            offerId: in_array(ReportFilter::Offer, $report->filters(), true) ? $this->offer : null,
            outletId: in_array(ReportFilter::Outlet, $report->filters(), true) ? $this->outlet : null,
        );
    }
}
