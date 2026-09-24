<?php

namespace App\Policies;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class BusinessPolicy
{
    use ChecksMembership;

    /**
     * Any active member can open their business.
     */
    public function view(User $user, Business $business): bool
    {
        return $user->membershipFor($business)?->isActive() ?? false;
    }

    /**
     * Change business details, unless it is suspended or waiting for review.
     */
    public function update(User $user, Business $business): bool
    {
        return $business->isWritable() && $this->memberCan($user, $business, Ability::ManageBusiness);
    }

    /**
     * Change what visitors see about the business, under the same conditions as its details.
     */
    public function updatePublicProfile(User $user, Business $business): bool
    {
        return $business->isWritable() && $this->memberCan($user, $business, Ability::ManagePublicContent);
    }

    /**
     * Send a draft or rejected business to admin review.
     */
    public function submit(User $user, Business $business): bool
    {
        return $business->onboarding_status->canBeSubmitted()
            && ! $business->isSuspended()
            && $this->memberCan($user, $business, Ability::ManageBusiness);
    }
}
