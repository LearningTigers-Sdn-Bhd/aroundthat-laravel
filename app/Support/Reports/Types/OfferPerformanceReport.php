<?php

namespace App\Support\Reports\Types;

use App\Enums\OfferStatus;
use App\Enums\ReportFilter;
use App\Enums\ReportGrouping;
use App\Enums\ReportValueFormat;
use App\Models\VoucherOffer;
use App\Support\Reports\Report;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportTile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * How each of the business's own offers did in the period: vouchers given out, how many came from partner sites,
 * how many were used and the discount they gave. Offers that ran in the period are listed even with nothing to show.
 *
 * Vouchers belong to the offer, not to an outlet, so the outlet choice narrows only the vouchers used.
 */
final class OfferPerformanceReport implements Report
{
    public function key(): string
    {
        return 'offers';
    }

    public function title(): string
    {
        return __('Offers');
    }

    public function question(): string
    {
        return __('Which offer works best?');
    }

    public function groupings(): array
    {
        return [ReportGrouping::Offer];
    }

    public function filters(): array
    {
        return [ReportFilter::Outlet];
    }

    public function columns(ReportFilters $filters): array
    {
        return [
            new ReportColumn('label', __('Offer'), ReportValueFormat::Text),
            new ReportColumn('status', __('Status'), ReportValueFormat::Text),
            new ReportColumn('given', __('Vouchers given out'), ReportValueFormat::Count),
            new ReportColumn('from_partners', __('From partner sites'), ReportValueFormat::Count),
            new ReportColumn('used', __('Vouchers used'), ReportValueFormat::Count),
            new ReportColumn('discount', __('Discount given'), ReportValueFormat::Money),
            new ReportColumn('left', __('Left to give'), ReportValueFormat::Text),
        ];
    }

    public function chartSeries(): array
    {
        return [];
    }

    public function rows(ReportFilters $filters): array
    {
        return array_values($this->offers($filters)->map(fn (VoucherOffer $offer): array => [
            'key' => $offer->id,
            'label' => $offer->name,
            'status' => $this->statusLabel($offer->state()),
            'given' => (int) $offer->getAttribute('given'),
            'from_partners' => (int) $offer->getAttribute('from_partners'),
            'used' => (int) $offer->getAttribute('used'),
            'discount' => $offer->getAttribute('discount'),
            'left' => $offer->voucher_limit === null ? __('No limit') : (string) ($offer->voucher_limit - $offer->issued_count),
            'hidden' => false,
        ])->all());
    }

    public function summary(ReportFilters $filters): array
    {
        $offers = $this->offers($filters);
        $best = $offers->first();
        $bestUsed = $best === null ? 0 : (int) $best->getAttribute('used');

        return [
            new ReportTile(__('Vouchers given out'), (int) $offers->sum('given'), ReportValueFormat::Count),
            new ReportTile(__('Vouchers used'), (int) $offers->sum('used'), ReportValueFormat::Count),
            $best !== null && $bestUsed > 0
                ? new ReportTile(__('Best offer'), $best->name, ReportValueFormat::Text, trans_choice(':count voucher used|:count vouchers used', $bestUsed))
                : new ReportTile(__('Best offer'), null, ReportValueFormat::Text, __('No vouchers used yet')),
        ];
    }

    /**
     * The business's offers that ran in the period, with their totals, most used first.
     *
     * @return Collection<int, VoucherOffer>
     */
    protected function offers(ReportFilters $filters): Collection
    {
        $period = $filters->period;

        $vouchers = DB::table('vouchers')
            ->selectRaw('voucher_offer_id, count(*) as given, count(*) filter (where integration_id is not null) as from_partners')
            ->where('created_at', '>=', $period->startsAt())
            ->where('created_at', '<', $period->endsAt())
            ->groupBy('voucher_offer_id');

        $redemptions = DB::table('redemptions')
            ->join('vouchers', 'vouchers.id', '=', 'redemptions.voucher_id')
            ->selectRaw(<<<'SQL'
                vouchers.voucher_offer_id,
                count(*) filter (where redemptions.cancelled_at is null) as used,
                sum(redemptions.discount_amount) filter (where redemptions.cancelled_at is null) as discount
                SQL)
            ->where('redemptions.redeemed_at', '>=', $period->startsAt())
            ->where('redemptions.redeemed_at', '<', $period->endsAt())
            ->unless(
                $filters->scope->includesOffersElsewhere && $filters->outletId === null,
                fn (Builder $query) => $query->whereIn('redemptions.outlet_id', $filters->outletIds()),
            )
            ->groupBy('vouchers.voucher_offer_id');

        return VoucherOffer::query()
            ->whereBelongsTo($filters->scope->business)
            ->where('voucher_offers.status', '!=', OfferStatus::Draft)
            ->where('voucher_offers.starts_at', '<', $period->endsAt())
            ->where('voucher_offers.ends_at', '>', $period->startsAt())
            ->leftJoinSub($vouchers, 'given', 'given.voucher_offer_id', '=', 'voucher_offers.id')
            ->leftJoinSub($redemptions, 'redeemed', 'redeemed.voucher_offer_id', '=', 'voucher_offers.id')
            ->select('voucher_offers.*')
            ->selectRaw(<<<'SQL'
                coalesce(given.given, 0) as given,
                coalesce(given.from_partners, 0) as from_partners,
                coalesce(redeemed.used, 0) as used,
                coalesce(redeemed.discount, 0)::numeric(14, 2)::text as discount
                SQL)
            ->orderByDesc('used')
            ->orderByDesc('given')
            ->orderBy('voucher_offers.name')
            ->get();
    }

    /**
     * The same words the offers list uses for an offer's state.
     */
    protected function statusLabel(string $state): string
    {
        return match ($state) {
            'active' => __('Active'),
            'paused' => __('Paused'),
            'scheduled' => __('Scheduled'),
            'ended' => __('Ended'),
            'hidden' => __('Hidden'),
            default => __('Draft'),
        };
    }
}
