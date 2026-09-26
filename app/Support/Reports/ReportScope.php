<?php

namespace App\Support\Reports;

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;

/**
 * What a member may see in reports. Every report query starts here, so no report can reach an outlet the member
 * cannot, or another business's data beyond the business's own offers.
 */
final readonly class ReportScope
{
    /**
     * @param  list<string>  $outletIds  the business's outlets the member reaches
     * @param  bool  $includesOffersElsewhere  whether the business's offers used at other businesses' outlets count too
     */
    public function __construct(
        public Business $business,
        public array $outletIds,
        public bool $includesOffersElsewhere,
    ) {}

    /**
     * Owners see every outlet, and their offers wherever they were used. Managers see their assigned outlets only.
     */
    public static function for(Membership $membership): self
    {
        return new self(
            business: $membership->business,
            outletIds: array_values($membership->accessibleOutlets()->get(['outlets.id'])->map(fn (Outlet $outlet): string => $outlet->id)->all()),
            includesOffersElsewhere: $membership->role->coversAllOutlets(),
        );
    }
}
