<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\BusinessData;
use App\Data\Admin\ChangeData;
use App\Data\Admin\OutletData;
use App\Data\Admin\TagData;
use App\Enums\OnboardingStatus;
use App\Enums\TagStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * How many of the oldest items each column shows before linking to the full list.
     */
    private const int ITEMS_PER_COLUMN = 25;

    /**
     * What is waiting for an admin: businesses and outlets submitted for review, tags owners created, and owners' edits to public content.
     */
    public function __invoke(): Response
    {
        $unreviewedChanges = Activity::query()->content()->unreviewed()
            ->with(['causer', 'reviewedBy', 'subject'])
            ->oldest()
            ->limit(self::ITEMS_PER_COLUMN)
            ->get()
            ->loadMorph('subject', [Outlet::class => ['business']]);

        return Inertia::render('admin/dashboard', [
            'pendingBusinessCount' => Business::where('onboarding_status', OnboardingStatus::Pending)->count(),
            'pendingTagCount' => Tag::where('status', TagStatus::Pending)->whereNull('merged_into_id')->count(),
            'unreviewedChangeCount' => Activity::query()->content()->unreviewed()->count(),
            'pendingOutletCount' => Outlet::where('onboarding_status', OnboardingStatus::Pending)->count(),
            'pendingBusinesses' => BusinessData::collect(
                Business::where('onboarding_status', OnboardingStatus::Pending)
                    ->with(['approvedBy', 'suspendedBy'])
                    ->oldest('submitted_at')
                    ->limit(self::ITEMS_PER_COLUMN)
                    ->get(),
            ),
            'pendingTags' => TagData::collect(
                Tag::where('status', TagStatus::Pending)
                    ->whereNull('merged_into_id')
                    ->with(['createdByBusiness', 'mergedInto'])
                    ->withCount('outlets')
                    ->oldest()
                    ->limit(self::ITEMS_PER_COLUMN)
                    ->get(),
            ),
            'unreviewedChanges' => ChangeData::collect(
                $unreviewedChanges->map(fn (Activity $change): ChangeData => ChangeData::fromModel($change)),
            ),
            'pendingOutlets' => OutletData::collect(
                Outlet::where('onboarding_status', OnboardingStatus::Pending)
                    ->with(['business', 'hostOutlet', 'approvedBy', 'suspendedBy'])
                    ->oldest('submitted_at')
                    ->limit(self::ITEMS_PER_COLUMN)
                    ->get(),
            ),
        ]);
    }
}
