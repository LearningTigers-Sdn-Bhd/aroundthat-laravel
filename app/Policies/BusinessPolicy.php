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

    public function update(User $user, Business $business): bool
    {
        return $this->memberCan($user, $business, Ability::ManageBusiness);
    }

    /**
     * Send a draft or rejected business to admin review.
     */
    public function submit(User $user, Business $business): bool
    {
        return $business->onboarding_status->canBeSubmitted()
            && $this->memberCan($user, $business, Ability::ManageBusiness);
    }
}
