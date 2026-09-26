<?php

namespace App\Data;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One member on a business's staff list. Load `user` and `outlets` first to avoid queries per member.
 */
#[MapName(SnakeCaseMapper::class)]
class MemberData extends Data
{
    /**
     * @param  array<int, OutletOptionData>  $outlets  Empty for owners, who cover every outlet.
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $name,
        public string $email,
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
            userId: $membership->user_id,
            name: $membership->user->name,
            email: $membership->user->email,
            role: $membership->role,
            outlets: $membership->outlets->sortBy('name')->map(fn (Outlet $outlet) => OutletOptionData::fromModel($outlet))->values()->all(),
            suspendedAt: $membership->suspended_at,
            suspensionReason: $membership->suspension_reason,
            joinedAt: $membership->created_at,
        );
    }
}
