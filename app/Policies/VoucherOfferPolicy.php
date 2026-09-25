<?php

namespace App\Policies;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\User;
use App\Models\VoucherOffer;
use App\Policies\Concerns\ChecksMembership;

class VoucherOfferPolicy
{
    use ChecksMembership;

    public function create(User $user, Business $business): bool
    {
        return ! $business->isSuspended()
            && $this->memberCan($user, $business, Ability::ManageOffers);
    }

    /**
     * Change the offer's terms, outlets or status, unless its business is suspended.
     */
    public function update(User $user, VoucherOffer $offer): bool
    {
        return ! $offer->business->isSuspended()
            && $this->memberCan($user, $offer->business_id, Ability::ManageOffers);
    }
}
