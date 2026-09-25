<?php

namespace App\Support\Reports\Types;

use App\Enums\EngagementEventType;
use App\Enums\ReportFilter;
use App\Enums\ReportGrouping;
use App\Enums\ReportValueFormat;
use App\Models\Outlet;
use App\Support\Reports\Report;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportTile;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How guests found the member's outlets on partner sites and apps: shown in a list, page opened, a link followed,
 * and vouchers claimed. Events a partner sent that look like abuse are left out. Every outlet here is the
 * business's own, so no privacy guard applies.
 */
final class PlaceVisitsReport implements Report
{
    public function key(): string
    {
        return 'place-visits';
    }

    public function title(): string
    {
        return __('Place visits');
    }

    public function question(): string
    {
        return __('How many people found your place on partner sites?');
    }

    public function groupings(): array
    {
        return [ReportGrouping::Day, ReportGrouping::Week, ReportGrouping::Month, ReportGrouping::Outlet];
    }

    public function filters(): array
    {
        return [ReportFilter::Outlet];
    }

    public function columns(ReportFilters $filters): array
    {
        return [
            new ReportColumn('label', $filters->grouping->label(), ReportValueFormat::Text),
            new ReportColumn('shown', __('Shown in lists'), ReportValueFormat::Count),
            new ReportColumn('opened', __('Page opened'), ReportValueFormat::Count),
            new ReportColumn('clicked', __('Links clicked'), ReportValueFormat::Count),
            new ReportColumn('claimed', __('Vouchers claimed'), ReportValueFormat::Count),
            new ReportColumn('claim_rate', __('Opened, then claimed'), ReportValueFormat::Percent),
        ];
    }

    public function chartSeries(): array
    {
        return ['opened', 'claimed'];
    }

    public function rows(ReportFilters $filters): array
    {
        return $filters->grouping === ReportGrouping::Outlet ? $this->outletRows($filters) : $this->timeRows($filters);
    }

    public function summary(ReportFilters $filters): array
    {
        $events = $this->counted($this->events($filters))->first();
        $claimed = $this->claims($filters)->count();

        return [
            new ReportTile(__('Page opened'), (int) $events->opened, ReportValueFormat::Count, trans_choice('Shown in lists :count time|Shown in lists :count times', (int) $events->shown)),
            new ReportTile(__('Links clicked'), (int) $events->clicked, ReportValueFormat::Count),
            new ReportTile(__('Vouchers claimed'), $claimed, ReportValueFormat::Count),
        ];
    }

    /**
     * The trusted events at the outlets in the period.
     */
    protected function events(ReportFilters $filters): Builder
    {
        return DB::table('engagement_events')
            ->whereIn('engagement_events.outlet_id', $filters->outletIds())
            ->where('engagement_events.suspect', false)
            ->where('engagement_events.occurred_at', '>=', $filters->period->startsAt())
            ->where('engagement_events.occurred_at', '<', $filters->period->endsAt());
    }

    /**
     * Add a count of each kind of event.
     */
    protected function counted(Builder $query): Builder
    {
        return $query->selectRaw(
            'count(*) filter (where engagement_events.event_type = ?) as shown, '
            .'count(*) filter (where engagement_events.event_type = ?) as opened, '
            .'count(*) filter (where engagement_events.event_type = ?) as clicked',
            [EngagementEventType::PlaceImpression->value, EngagementEventType::PlaceView->value, EngagementEventType::OutboundClick->value],
        );
    }

    /**
     * Vouchers partners claimed for guests from the outlets' pages in the period.
     */
    protected function claims(ReportFilters $filters): Builder
    {
        return DB::table('vouchers')
            ->whereNotNull('vouchers.integration_id')
            ->whereIn('vouchers.outlet_id', $filters->outletIds())
            ->where('vouchers.created_at', '>=', $filters->period->startsAt())
            ->where('vouchers.created_at', '<', $filters->period->endsAt());
    }

    /**
     * One row for every day, week or month of the period, empty ones included.
     *
     * @return list<array<string, mixed>>
     */
    protected function timeRows(ReportFilters $filters): array
    {
        $period = $filters->period;
        $events = $period->selectBucket($this->counted($this->events($filters)), 'engagement_events.occurred_at', $filters->grouping)->get()->keyBy('bucket');
        $claims = $period->selectBucket($this->claims($filters)->selectRaw('count(*) as claimed'), 'vouchers.created_at', $filters->grouping)->pluck('claimed', 'bucket');

        $rows = [];

        foreach ($period->buckets($filters->grouping) as $start) {
            $key = $start->toDateString();

            $rows[] = [
                'key' => $key,
                'label' => $filters->grouping->bucketLabel($start),
                ...$this->measures($events->get($key), (int) $claims->get($key, 0)),
                'hidden' => false,
            ];
        }

        return $rows;
    }

    /**
     * One row per outlet, most opened first. Archived outlets appear only when they have something to show.
     *
     * @return list<array<string, mixed>>
     */
    protected function outletRows(ReportFilters $filters): array
    {
        $events = $this->counted($this->events($filters))
            ->addSelect('engagement_events.outlet_id')
            ->groupBy('engagement_events.outlet_id')
            ->get()
            ->keyBy('outlet_id');
        $claims = $this->claims($filters)->selectRaw('vouchers.outlet_id, count(*) as claimed')->groupBy('vouchers.outlet_id')->pluck('claimed', 'outlet_id');

        return array_values(Outlet::query()
            ->whereKey($filters->outletIds())
            ->get(['id', 'name', 'archived_at'])
            ->map(fn (Outlet $outlet): array => [
                'key' => $outlet->id,
                'label' => $outlet->name,
                ...$this->measures($events->get($outlet->id), (int) $claims->get($outlet->id, 0)),
                'hidden' => false,
                'archived' => $outlet->isArchived(),
            ])
            ->reject(fn (array $row): bool => $row['archived'] && $row['shown'] + $row['opened'] + $row['clicked'] + $row['claimed'] === 0)
            ->map(fn (array $row): array => array_diff_key($row, ['archived' => true]))
            ->sortBy([['opened', 'desc'], ['label', 'asc']])
            ->all());
    }

    /**
     * The counts of one group, and the share of page opens that ended in a claim, or null with no page opens.
     *
     * @return array{shown: int, opened: int, clicked: int, claimed: int, claim_rate: float|null}
     */
    protected function measures(?object $events, int $claimed): array
    {
        $opened = (int) ($events->opened ?? 0);

        return [
            'shown' => (int) ($events->shown ?? 0),
            'opened' => $opened,
            'clicked' => (int) ($events->clicked ?? 0),
            'claimed' => $claimed,
            'claim_rate' => $opened === 0 ? null : round($claimed / $opened * 100, 1),
        ];
    }
}
