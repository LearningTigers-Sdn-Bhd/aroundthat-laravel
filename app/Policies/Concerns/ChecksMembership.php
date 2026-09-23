<?php

namespace App\Policies\Concerns;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\User;

trait ChecksMembership
{
    /**
     * Whether the user's own membership at the business allows the ability.
     */
    protected function memberCan(User $user, Business|string $business, Ability $ability): bool
    {
        return $user->membershipFor($business)?->can($ability) ?? false;
    }
}
