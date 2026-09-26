<?php

namespace App\Http\Controllers\App;

use App\Data\DashboardOfferData;
use App\Data\DashboardRedemptionData;
use App\Data\DashboardTileData;
use App\Enums\Ability;
use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use App\Http\Controllers\Controller;
use App\Models\Redemption;
use App\Support\Dashboard\DashboardAttention;
use App\Support\Dashboard\DashboardQuickActions;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportScope;
use App\Support\Reports\ReportTile;
use App\Support\Reports\Types\OfferPerformanceReport;
use App\Support\Reports\Types\RedemptionsReport;
use App\Support\Workspace;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The first page in a business: this month's headline numbers, quick actions, what still needs doing, the latest
 * vouchers used and running offers. Each part shows only to members whose role allows it.
 */
class DashboardController extends Controller
{
    /** How many rows the recent lists show. */
    protected const int RECENT_LIMIT = 5;

    public function __construct(protected Workspace $workspace) {}

    public function __invoke(DashboardQuickActions $quickActions, DashboardAttention $attention): Response
    {
        $membership = $this->workspace->membership();
        $viewsReports = $membership->can(Ability::ViewReports);
        $scope = ReportScope::for($membership);

        return Inertia::render('dashboard', [
            'month' => $viewsReports ? $this->month($scope) : null,
            'today' => ! $viewsReports && $membership->can(Ability::ViewTodayActivity) ? $this->today($scope) : null,
            'quickActions' => $quickActions->for($membership),
            'attention' => $attention->for($membership),
            'recentRedemptions' => $viewsReports ? Inertia::defer(fn () => DashboardRedemptionData::collect(
                Redemption::query()->coveredBy($scope)->with(['voucher.offer', 'outlet'])->latest('redeemed_at')->limit(self::RECENT_LIMIT)->get(),
            )) : null,
            'offers' => $membership->can(Ability::ManageOffers) ? Inertia::defer(fn () => DashboardOfferData::collect(
                $this->workspace->business()->voucherOffers()->running()->orderBy('ends_at')->limit(self::RECENT_LIMIT)->get(),
            )) : null,
        ]);
    }

    /**
     * @return array{label: string, currency: string, tiles: list<DashboardTileData>}
     */
    protected function month(ReportScope $scope): array
    {
        $redemptions = app(RedemptionsReport::class);
        $offers = app(OfferPerformanceReport::class);
        $period = ReportPeriod::preset(ReportPeriodPreset::ThisMonth, $scope->business->timezone);

        [$used, , $discount, $paid] = $redemptions->summary(new ReportFilters($scope, $period, ReportGrouping::Day));
        $best = $offers->summary(new ReportFilters($scope, $period, ReportGrouping::Offer))[2];

        return [
            'label' => $period->label(),
            'currency' => config('vouchers.currency'),
            'tiles' => [
                ...array_map(fn (ReportTile $tile) => DashboardTileData::fromTile($tile, $redemptions->key()), [$used, $discount, $paid]),
                DashboardTileData::fromTile($best, $offers->key()),
            ],
        ];
    }

    /**
     * Vouchers used today, in the business's time zone, at the member's outlets.
     *
     * @return array{used: int}
     */
    protected function today(ReportScope $scope): array
    {
        return [
            'used' => Redemption::query()
                ->whereIn('outlet_id', $scope->outletIds)
                ->whereNull('cancelled_at')
                ->where('redeemed_at', '>=', now($scope->business->timezone)->startOfDay()->utc())
                ->count(),
        ];
    }
}
