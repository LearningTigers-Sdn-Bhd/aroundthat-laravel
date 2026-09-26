<?php

namespace App\Policies;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class OutletPolicy
{
    use ChecksMembership;

    /**
     * Owners see every outlet of their business; managers and cashiers only assigned ones.
     */
    public function view(User $user, Outlet $outlet): bool
    {
        return $user->membershipFor($outlet->business_id)?->canAccessOutlet($outlet) ?? false;
    }

    public function create(User $user, Business $business): bool
    {
        return ! $business->isSuspended()
            && $this->memberCan($user, $business, Ability::ManageOutlets);
    }

    /**
     * Change outlet details, unless it is archived, suspended or waiting for review.
     */
    public function update(User $user, Outlet $outlet): bool
    {
        return $outlet->isWritable()
            && $this->memberCan($user, $outlet->business_id, Ability::ManageOutlets);
    }

    /**
     * Change what visitors see about the outlet, under the same conditions as its details.
     */
    public function updatePublicProfile(User $user, Outlet $outlet): bool
    {
        return $outlet->isWritable()
            && $this->memberCan($user, $outlet->business_id, Ability::ManagePublicContent);
    }

    /**
     * Send a draft or rejected outlet to admin review, once its business is approved.
     */
    public function submit(User $user, Outlet $outlet): bool
    {
        return $outlet->onboarding_status->canBeSubmitted()
            && $outlet->business->isApproved()
            && $this->update($user, $outlet);
    }

    /**
     * Archive or restore an outlet the business no longer operates.
     */
    public function archive(User $user, Outlet $outlet): bool
    {
        return ! $outlet->business->isSuspended()
            && $this->memberCan($user, $outlet->business_id, Ability::ManageOutlets);
    }
}
