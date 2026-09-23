<?php

namespace App\Data;

use App\Enums\Ability;
use App\Enums\MembershipRole;
use App\Enums\OnboardingStatus;
use App\Models\Membership;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The business the user is working in, and what they may do there. Shared with every business page.
 */
#[MapName(SnakeCaseMapper::class)]
class CurrentWorkspaceData extends Data
{
    /**
     * @param  list<Ability>  $abilities
     */
    public function __construct(
        public string $businessId,
        public string $businessName,
        public OnboardingStatus $onboardingStatus,
        public bool $isSuspended,
        public MembershipRole $role,
        public array $abilities,
    ) {}

    public static function fromMembership(Membership $membership): self
    {
        return new self(
            businessId: $membership->business_id,
            businessName: $membership->business->name,
            onboardingStatus: $membership->business->onboarding_status,
            isSuspended: $membership->business->isSuspended(),
            role: $membership->role,
            abilities: array_values(array_filter(Ability::cases(), $membership->can(...))),
        );
    }
}
