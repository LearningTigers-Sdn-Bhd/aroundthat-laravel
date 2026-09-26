<?php

namespace App\Support\Dashboard;

use App\Enums\Ability;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\VoucherOffer;

/**
 * The dashboard shortcuts a member may take, most used first.
 */
class DashboardQuickActions
{
    /**
     * @return list<'counter'|'new_offer'|'new_outlet'|'invite_staff'|'export_redemptions'>
     */
    public function for(Membership $membership): array
    {
        $user = $membership->user;
        $business = $membership->business;

        return array_keys(array_filter([
            'counter' => $membership->can(Ability::Scan),
            'new_offer' => $user->can('create', [VoucherOffer::class, $business]),
            'new_outlet' => $user->can('create', [Outlet::class, $business]),
            'invite_staff' => $membership->can(Ability::ManageStaff),
            'export_redemptions' => $membership->can(Ability::ViewReports),
        ]));
    }
}
