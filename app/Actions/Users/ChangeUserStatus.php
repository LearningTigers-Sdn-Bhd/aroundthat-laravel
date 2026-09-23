<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use App\Support\EnsureBusinessKeepsAnOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin suspends or reactivates a login. A suspended user is signed out on their next request.
 */
class ChangeUserStatus
{
    public function __construct(
        protected AuditTrail $audit,
        protected EnsureBusinessKeepsAnOwner $ownerGuard,
    ) {}

    /**
     * @throws ValidationException
     */
    public function suspend(User $admin, User $user, string $reason): User
    {
        return DB::transaction(function () use ($admin, $user, $reason): User {
            if ($user->is_admin) {
                User::query()->where('is_admin', true)->lockForUpdate()->get();
            }

            $user = $user->lockedForUpdate();

            $refusal = match (true) {
                $user->is($admin) => __('You cannot suspend your own login.'),
                $user->isSuspended() => __('This login is already suspended.'),
                $user->is_admin && ! $this->anotherActiveAdminExists($user) => __('At least one active admin must remain.'),
                default => null,
            };

            if ($refusal !== null) {
                throw ValidationException::withMessages(['user' => $refusal]);
            }

            $this->ownerGuard->forUser($user);

            return $this->audit->as('suspended', $reason, function () use ($admin, $user, $reason): User {
                $user->forceFill([
                    'suspended_at' => now(),
                    'suspended_by_id' => $admin->getKey(),
                    'suspension_reason' => $reason,
                ])->save();

                return $user;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user = $user->lockedForUpdate();

            if (! $user->isSuspended()) {
                throw ValidationException::withMessages(['user' => __('This login is not suspended.')]);
            }

            return $this->audit->as('reactivated', null, function () use ($user): User {
                $user->forceFill([
                    'suspended_at' => null,
                    'suspended_by_id' => null,
                    'suspension_reason' => null,
                ])->save();

                return $user;
            });
        });
    }

    protected function anotherActiveAdminExists(User $user): bool
    {
        return User::query()
            ->where('is_admin', true)
            ->whereNull('suspended_at')
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
