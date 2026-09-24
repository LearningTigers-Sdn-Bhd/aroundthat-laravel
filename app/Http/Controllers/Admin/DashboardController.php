<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\OutletData;
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
     * What is waiting for an admin: businesses and outlets submitted for review, tags owners created, and owners' edits to public content.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'pendingBusinessCount' => Business::where('onboarding_status', OnboardingStatus::Pending)->count(),
            'pendingTagCount' => Tag::where('status', TagStatus::Pending)->whereNull('merged_into_id')->count(),
            'unreviewedChangeCount' => Activity::query()->content()->unreviewed()->count(),
            'pendingOutlets' => OutletData::collect(
                Outlet::where('onboarding_status', OnboardingStatus::Pending)
                    ->with(['business', 'hostOutlet', 'approvedBy', 'suspendedBy'])
                    ->oldest('submitted_at')
                    ->limit(25)
                    ->get(),
            ),
        ]);
    }
}
