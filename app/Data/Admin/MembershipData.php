<?php

namespace App\Data\Admin;

use App\Data\OutletOptionData;
use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One business a user belongs to, as admins see it on the user's page. Load `business` and `outlets` first.
 */
#[MapName(SnakeCaseMapper::class)]
class MembershipData extends Data
{
    /**
     * @param  array<int, OutletOptionData>  $outlets  Empty for owners, who cover every outlet.
     */
    public function __construct(
        public string $id,
        public string $businessId,
        public string $businessName,
        public MembershipRole $role,
        public array $outlets,
        public ?CarbonInterface $suspendedAt,
        public ?string $suspensionReason,
        public ?CarbonInterface $joinedAt,
    ) {}

    public static function fromModel(Membership $membership): self
    {
        return new self(
            id: $membership->id,
            businessId: $membership->business_id,
            businessName: $membership->business->name,
            role: $membership->role,
            outlets: $membership->outlets->sortBy('name')->map(fn (Outlet $outlet) => OutletOptionData::fromModel($outlet))->values()->all(),
            suspendedAt: $membership->suspended_at,
            suspensionReason: $membership->suspension_reason,
            joinedAt: $membership->created_at,
        );
    }
}
