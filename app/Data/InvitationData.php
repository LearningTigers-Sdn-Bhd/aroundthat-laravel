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
 * An invitation on a business's staff page. Load `invitedBy` and `outlets` first to avoid queries per invitation.
 */
#[MapName(SnakeCaseMapper::class)]
class InvitationData extends Data
{
    /**
     * @param  array<int, OutletOptionData>  $outlets
     */
    public function __construct(
        public string $id,
        public string $email,
        public MembershipRole $role,
        public InvitationStatus $status,
        public array $outlets,
        public ?string $invitedByName,
        public ?CarbonInterface $sentAt,
        public CarbonInterface $expiresAt,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Invitation $invitation): self
    {
        return new self(
            id: $invitation->id,
            email: $invitation->email,
            role: $invitation->role,
            status: $invitation->status(),
            outlets: $invitation->outlets->sortBy('name')->map(fn (Outlet $outlet) => OutletOptionData::fromModel($outlet))->values()->all(),
            invitedByName: $invitation->invitedBy?->name,
            sentAt: $invitation->sent_at,
            expiresAt: $invitation->expires_at,
            createdAt: $invitation->created_at,
        );
    }
}
