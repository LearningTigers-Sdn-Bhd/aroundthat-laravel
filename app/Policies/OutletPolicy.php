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
        return $this->memberCan($user, $business, Ability::ManageOutlets);
    }

    public function update(User $user, Outlet $outlet): bool
    {
        return ! $outlet->isArchived()
            && $this->memberCan($user, $outlet->business_id, Ability::ManageOutlets);
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
}
