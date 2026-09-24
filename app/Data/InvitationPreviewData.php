<?php

namespace App\Data;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What the invited person sees when they open their link.
 */
#[MapName(SnakeCaseMapper::class)]
class InvitationPreviewData extends Data
{
    /**
     * @param  array<int, string>  $outletNames
     */
    public function __construct(
        public string $businessName,
        public ?string $inviterName,
        public string $email,
        public MembershipRole $role,
        public InvitationStatus $status,
        public CarbonInterface $expiresAt,
        public array $outletNames,
    ) {}

    public static function fromInvitation(Invitation $invitation): self
    {
        return new self(
            businessName: $invitation->business->name,
            inviterName: $invitation->invitedBy?->name,
            email: $invitation->email,
            role: $invitation->role,
            status: $invitation->status(),
            expiresAt: $invitation->expires_at,
            outletNames: $invitation->outlets()->orderBy('name')->get()->map(fn (Outlet $outlet): string => $outlet->name)->all(),
        );
    }
}
