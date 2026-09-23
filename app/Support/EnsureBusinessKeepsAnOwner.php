<?php

namespace App\Support;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Stops any change that would leave a business without an active owner.
 *
 * Call it inside the transaction that makes the change: it locks the owner rows so two
 * concurrent changes cannot each remove "the other" last owner.
 */
class EnsureBusinessKeepsAnOwner
{
    /**
     * Before demoting, suspending or removing this membership.
     *
     * @throws ValidationException
     */
    public function forMembership(Membership $membership): void
    {
        $this->ensureAnotherOwner($membership->business_id, $membership->user_id);
    }

    /**
     * Before suspending or deleting this user.
     *
     * @throws ValidationException
     */
    public function forUser(User $user): void
    {
        $user->memberships()
            ->usableOwners()
            ->pluck('business_id')
            ->each(fn (string $businessId) => $this->ensureAnotherOwner($businessId, $user->getKey()));
    }

    /**
     * @throws ValidationException
     */
    protected function ensureAnotherOwner(string $businessId, string $leavingUserId): void
    {
        $ownerUserIds = Membership::query()
            ->usableOwners()
            ->where('business_id', $businessId)
            ->lockForUpdate()
            ->pluck('user_id');

        if ($ownerUserIds->contains($leavingUserId) && $ownerUserIds->count() === 1) {
            throw ValidationException::withMessages([
                'membership' => __('A business needs at least one active owner. Add another owner first.'),
            ]);
        }
    }
}
