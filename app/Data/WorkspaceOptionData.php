<?php

namespace App\Data;

use App\Enums\MembershipRole;
use App\Models\Membership;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A business the user can switch to.
 */
#[MapName(SnakeCaseMapper::class)]
class WorkspaceOptionData extends Data
{
    public function __construct(
        public string $businessId,
        public string $businessName,
        public MembershipRole $role,
    ) {}

    public static function fromMembership(Membership $membership): self
    {
        return new self(
            businessId: $membership->business_id,
            businessName: $membership->business->name,
            role: $membership->role,
        );
    }
}
