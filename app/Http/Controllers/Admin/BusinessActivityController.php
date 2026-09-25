<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\ActivityData;
use App\Data\Admin\BusinessData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BusinessActivityController extends Controller
{
    /**
     * The Activity tab of a business: the latest changes to it and to its outlets, members and invitations.
     */
    public function index(Business $business): Response
    {
        $business->load(['approvedBy', 'suspendedBy']);

        return Inertia::render('admin/businesses/activity', [
            'business' => BusinessData::fromModel($business),
            'activities' => Inertia::defer(fn () => ActivityData::collect($this->activities($business))),
        ]);
    }

    /**
     * @return Collection<int, Activity>
     */
    protected function activities(Business $business): Collection
    {
        $subjects = [
            'business' => [$business->id],
            'outlet' => $business->outlets()->pluck('id')->all(),
            'membership' => $business->memberships()->pluck('id')->all(),
            'invitation' => $business->invitations()->pluck('id')->all(),
        ];

        return Activity::query()
            ->where(function (Builder $query) use ($subjects): void {
                foreach ($subjects as $type => $ids) {
                    $query->orWhere(fn (Builder $query) => $query->where('subject_type', $type)->whereIn('subject_id', $ids));
                }
            })
            ->with('causer')
            ->latest('id')
            ->limit(100)
            ->get();
    }
}
