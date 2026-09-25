<?php

namespace App\Http\Controllers\App;

use App\Data\DashboardTileData;
use App\Enums\Ability;
use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use App\Http\Controllers\Controller;
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
 * The first page in a business. Members who may see reports get this month's headline numbers; the rest a way in.
 */
class DashboardController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function __invoke(RedemptionsReport $redemptions, OfferPerformanceReport $offers): Response
    {
        if (! $this->workspace->membership()->can(Ability::ViewReports)) {
            return Inertia::render('dashboard', ['month' => null]);
        }

        $period = ReportPeriod::preset(ReportPeriodPreset::ThisMonth, $this->workspace->business()->timezone);
        $filters = new ReportFilters(ReportScope::for($this->workspace->membership()), $period, ReportGrouping::Day);

        [$used, , $discount, $paid] = $redemptions->summary($filters);
        $best = $offers->summary(new ReportFilters($filters->scope, $period, ReportGrouping::Offer))[2];

        return Inertia::render('dashboard', [
            'month' => [
                'label' => $period->label(),
                'currency' => config('vouchers.currency'),
                'tiles' => [
                    ...array_map(fn (ReportTile $tile) => DashboardTileData::fromTile($tile, $redemptions->key()), [$used, $discount, $paid]),
                    DashboardTileData::fromTile($best, $offers->key()),
                ],
            ],
        ]);
    }
}
