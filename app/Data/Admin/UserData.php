<?php

namespace App\Data\Admin;

use App\Models\User;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A login as admins see it. Load `suspendedBy` first to avoid a query per user.
 */
#[MapName(SnakeCaseMapper::class)]
class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public bool $isAdmin,
        public bool $isEmailVerified,
        public bool $mustChangePassword,
        public bool $hasTwoFactor,
        public ?CarbonInterface $lastLoginAt,
        public ?CarbonInterface $suspendedAt,
        public ?string $suspendedByName,
        public ?string $suspensionReason,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            isAdmin: $user->is_admin,
            isEmailVerified: $user->hasVerifiedEmail(),
            mustChangePassword: $user->must_change_password,
            hasTwoFactor: $user->two_factor_confirmed_at !== null,
            lastLoginAt: $user->last_login_at,
            suspendedAt: $user->suspended_at,
            suspendedByName: $user->suspendedBy?->name,
            suspensionReason: $user->suspension_reason,
            createdAt: $user->created_at,
        );
    }
}
