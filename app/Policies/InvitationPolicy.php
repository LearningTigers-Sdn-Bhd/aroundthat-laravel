<?php

namespace App\Policies;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class InvitationPolicy
{
    use ChecksMembership;

    /**
     * Invite someone to a business that is not suspended.
     */
    public function create(User $user, Business $business): bool
    {
        return ! $business->isSuspended()
            && $this->memberCan($user, $business, Ability::ManageStaff);
    }

    /**
     * Resend or cancel an invitation the business sent.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return $this->memberCan($user, $invitation->business_id, Ability::ManageStaff);
    }
}
