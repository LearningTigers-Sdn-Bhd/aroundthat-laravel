<?php

namespace App\Actions\Staff;

use App\Models\Membership;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use App\Support\EnsureBusinessKeepsAnOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Suspend, reactivate or remove a member of a business. A business always keeps an active owner.
 */
class ChangeMemberStatus
{
    public function __construct(
        protected AuditTrail $audit,
        protected EnsureBusinessKeepsAnOwner $ownerGuard,
    ) {}

    /**
     * @throws ValidationException
     */
    public function suspend(User $actor, Membership $membership, string $reason): Membership
    {
        return DB::transaction(function () use ($actor, $membership, $reason): Membership {
            $membership->business->lockedForUpdate();
            $membership = $membership->lockedForUpdate();

            if (! $membership->isActive()) {
                throw ValidationException::withMessages(['membership' => __('This member is already suspended.')]);
            }

            $this->ownerGuard->forMembership($membership);

            return $this->audit->as('suspended', $reason, function () use ($actor, $membership, $reason): Membership {
                $membership->forceFill([
                    'suspended_at' => now(),
                    'suspended_by_id' => $actor->getKey(),
                    'suspension_reason' => $reason,
                ])->save();

                return $membership;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Membership $membership): Membership
    {
        return DB::transaction(function () use ($membership): Membership {
            $membership = $membership->lockedForUpdate();

            if ($membership->isActive()) {
                throw ValidationException::withMessages(['membership' => __('This member is not suspended.')]);
            }

            return $this->audit->as('reactivated', null, function () use ($membership): Membership {
                $membership->forceFill([
                    'suspended_at' => null,
                    'suspended_by_id' => null,
                    'suspension_reason' => null,
                ])->save();

                return $membership;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function remove(Membership $membership): void
    {
        DB::transaction(function () use ($membership): void {
            $membership->business->lockedForUpdate();
            $membership = $membership->lockedForUpdate();

            $this->ownerGuard->forMembership($membership);

            $this->audit->as('removed', null, fn () => $membership->delete());
        });
    }
}
