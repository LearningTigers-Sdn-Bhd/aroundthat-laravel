<?php

namespace App\Support\Reports\Types;

use App\Enums\ReportFilter;
use App\Enums\ReportGrouping;
use App\Enums\ReportValueFormat;
use App\Support\Reports\Report;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPrivacyGuard;
use App\Support\Reports\ReportTile;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Vouchers used at the member's outlets and, for owners, the business's own offers used at sponsored outlets of
 * other businesses. Cancelled redemptions are left out of every total and only counted.
 */
final class RedemptionsReport implements Report
{
    /** The row keys that hold numbers, emptied when the privacy guard hides a row. */
    protected const array MEASURES = ['used', 'bills', 'discount', 'paid', 'cancelled'];

    public function __construct(protected ReportPrivacyGuard $guard) {}

    public function key(): string
    {
        return 'redemptions';
    }

    public function title(): string
    {
        return __('Vouchers used');
    }

    public function question(): string
    {
        return __('How many vouchers were used, and how much discount did you give?');
    }

    public function groupings(): array
    {
        return [ReportGrouping::Day, ReportGrouping::Week, ReportGrouping::Month, ReportGrouping::Outlet, ReportGrouping::Offer];
    }

    public function filters(): array
    {
        return [ReportFilter::Offer, ReportFilter::Outlet];
    }

    public function columns(ReportFilters $filters): array
    {
        return [
            new ReportColumn('label', $filters->grouping->label(), ReportValueFormat::Text),
            new ReportColumn('used', __('Vouchers used'), ReportValueFormat::Count),
            new ReportColumn('bills', __('Total bills'), ReportValueFormat::Money),
            new ReportColumn('discount', __('Discount given'), ReportValueFormat::Money),
            new ReportColumn('paid', __('Customers paid'), ReportValueFormat::Money),
            new ReportColumn('cancelled', __('Cancelled'), ReportValueFormat::Count),
        ];
    }

    public function rows(ReportFilters $filters): array
    {
        return match ($filters->grouping) {
            ReportGrouping::Outlet => $this->outletRows($filters)['rows'],
            ReportGrouping::Offer => $this->offerRows($filters),
            default => $this->timeRows($filters),
        };
    }

    public function summary(ReportFilters $filters): array
    {
        $totals = $this->measured($this->redemptions($filters))->first();
        $cancelled = (int) $totals->cancelled;

        if ($filters->grouping === ReportGrouping::Outlet && $this->outletRows($filters)['summaryHidden']) {
            return [
                new ReportTile(__('Vouchers used'), null, ReportValueFormat::Count, __('Hidden for privacy')),
                new ReportTile(__('Total bills'), null, ReportValueFormat::Money, __('Hidden for privacy')),
                new ReportTile(__('Discount given'), null, ReportValueFormat::Money, __('Hidden for privacy')),
                new ReportTile(__('Customers paid'), null, ReportValueFormat::Money, __('Hidden for privacy')),
            ];
        }

        return [
            new ReportTile(__('Vouchers used'), (int) $totals->used, ReportValueFormat::Count, $cancelled > 0 ? trans_choice(':count cancelled, not counted|:count cancelled, not counted', $cancelled) : null),
            new ReportTile(__('Total bills'), $totals->bills, ReportValueFormat::Money),
            new ReportTile(__('Discount given'), $totals->discount, ReportValueFormat::Money),
            new ReportTile(__('Customers paid'), $totals->paid, ReportValueFormat::Money),
        ];
    }

    /**
     * Every redemption in the period the member may see, narrowed by the chosen offer or outlet.
     */
    protected function redemptions(ReportFilters $filters): Builder
    {
        $business = $filters->scope->business;

        return DB::table('redemptions')
            ->join('vouchers', 'vouchers.id', '=', 'redemptions.voucher_id')
            ->join('voucher_offers', 'voucher_offers.id', '=', 'vouchers.voucher_offer_id')
            ->where('redemptions.redeemed_at', '>=', $filters->period->startsAt())
            ->where('redemptions.redeemed_at', '<', $filters->period->endsAt())
            ->where(function (Builder $query) use ($filters, $business): void {
                $query->whereIn('redemptions.outlet_id', $filters->outletIds());

                if ($filters->scope->includesOffersElsewhere && $filters->outletId === null) {
                    $query->orWhere('voucher_offers.business_id', $business->id);
                }
            })
            ->when($filters->offerId, fn (Builder $query, string $offerId) => $query->where('voucher_offers.id', $offerId));
    }

    /**
     * Add the totals, leaving cancelled redemptions out of all but their own count.
     */
    protected function measured(Builder $query): Builder
    {
        return $query->selectRaw(<<<'SQL'
            count(*) filter (where redemptions.cancelled_at is null) as used,
            coalesce(sum(redemptions.bill_amount) filter (where redemptions.cancelled_at is null), 0)::numeric(14, 2)::text as bills,
            coalesce(sum(redemptions.discount_amount) filter (where redemptions.cancelled_at is null), 0)::numeric(14, 2)::text as discount,
            coalesce(sum(redemptions.bill_amount - redemptions.discount_amount) filter (where redemptions.cancelled_at is null), 0)::numeric(14, 2)::text as paid,
            count(*) filter (where redemptions.cancelled_at is not null) as cancelled
            SQL);
    }

    /**
     * One row for every day, week or month of the period, empty ones included.
     *
     * @return list<array<string, mixed>>
     */
    protected function timeRows(ReportFilters $filters): array
    {
        $found = $filters->period
            ->selectBucket($this->measured($this->redemptions($filters)), 'redemptions.redeemed_at', $filters->grouping)
            ->get()
            ->keyBy('bucket');

        $rows = [];

        foreach ($filters->period->buckets($filters->grouping) as $start) {
            $totals = $found->get($start->toDateString());

            $rows[] = [
                'key' => $start->toDateString(),
                'label' => $filters->grouping->bucketLabel($start),
                ...$this->measures($totals),
                'hidden' => false,
            ];
        }

        return $rows;
    }

    /**
     * One row per outlet with redemptions: the business's own first, then sponsored outlets of other businesses,
     * named with their business and guarded for privacy.
     *
     * @return array{rows: list<array<string, mixed>>, summaryHidden: bool}
     */
    protected function outletRows(ReportFilters $filters): array
    {
        $businessId = $filters->scope->business->id;

        $rows = $this->measured($this->redemptions($filters))
            ->join('outlets', 'outlets.id', '=', 'redemptions.outlet_id')
            ->join('businesses', 'businesses.id', '=', 'outlets.business_id')
            ->addSelect('outlets.id as outlet_id', 'outlets.name as outlet_name', 'outlets.business_id', 'businesses.name as business_name')
            ->groupBy('outlets.id', 'outlets.name', 'outlets.business_id', 'businesses.name')
            ->get()
            ->map(fn (object $totals): array => [
                'key' => $totals->outlet_id,
                'label' => $totals->business_id === $businessId ? $totals->outlet_name : "{$totals->outlet_name} ({$totals->business_name})",
                ...$this->measures($totals),
                'privacy_count' => (int) $totals->used,
                'protected' => $totals->business_id !== $businessId,
            ])
            ->sortBy([['protected', 'asc'], ['used', 'desc'], ['label', 'asc']])
            ->all();

        return $this->guard->protect(array_values($rows), self::MEASURES);
    }

    /**
     * One row per offer, busiest first. Another business's offer used at the member's outlets is named with it.
     *
     * @return list<array<string, mixed>>
     */
    protected function offerRows(ReportFilters $filters): array
    {
        $businessId = $filters->scope->business->id;

        return array_values($this->measured($this->redemptions($filters))
            ->join('businesses', 'businesses.id', '=', 'voucher_offers.business_id')
            ->addSelect('voucher_offers.id as offer_id', 'voucher_offers.name as offer_name', 'voucher_offers.business_id', 'businesses.name as business_name')
            ->groupBy('voucher_offers.id', 'voucher_offers.name', 'voucher_offers.business_id', 'businesses.name')
            ->get()
            ->map(fn (object $totals): array => [
                'key' => $totals->offer_id,
                'label' => $totals->business_id === $businessId ? $totals->offer_name : "{$totals->offer_name} ({$totals->business_name})",
                ...$this->measures($totals),
                'hidden' => false,
            ])
            ->sortBy([['used', 'desc'], ['label', 'asc']])
            ->all());
    }

    /**
     * The measures of one group, or zeros for an empty one.
     *
     * @return array{used: int, bills: string, discount: string, paid: string, cancelled: int}
     */
    protected function measures(?object $totals): array
    {
        return [
            'used' => (int) ($totals->used ?? 0),
            'bills' => $totals->bills ?? '0.00',
            'discount' => $totals->discount ?? '0.00',
            'paid' => $totals->paid ?? '0.00',
            'cancelled' => (int) ($totals->cancelled ?? 0),
        ];
    }
}
