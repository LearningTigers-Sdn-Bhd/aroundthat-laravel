<?php

namespace App\Policies;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class MembershipPolicy
{
    use ChecksMembership;

    /**
     * List the staff of a business.
     */
    public function viewAny(User $user, Business $business): bool
    {
        return $this->memberCan($user, $business, Ability::ManageStaff);
    }

    /**
     * Change another member's role, outlets or suspension. Nobody manages their own membership.
     */
    public function update(User $user, Membership $membership): bool
    {
        return $membership->user_id !== $user->getKey()
            && $this->memberCan($user, $membership->business_id, Ability::ManageStaff);
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $this->update($user, $membership);
    }
}
