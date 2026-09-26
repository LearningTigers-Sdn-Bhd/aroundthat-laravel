<?php

namespace App\Actions\Staff;

use App\Data\Forms\NewAccountData;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The invited person's answer: join the business, or turn the invitation down.
 *
 * Whoever holds the link proves they own the invited email, so accepting also verifies it.
 */
class RespondToInvitation
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * Join the business. Someone with a login must be logged in as it; someone without one gets a new login.
     *
     * @param  User|null  $currentUser  Who is logged in, if anyone.
     * @param  NewAccountData|null  $account  Required when the invited email has no login yet.
     *
     * @throws ValidationException
     */
    public function accept(Invitation $invitation, ?User $currentUser, ?NewAccountData $account = null): Membership
    {
        return DB::transaction(function () use ($invitation, $currentUser, $account): Membership {
            $invitation = $invitation->lockedForUpdate();
            $this->ensurePending($invitation);

            $business = $invitation->business->lockedForUpdate();

            if ($business->isSuspended()) {
                throw ValidationException::withMessages(['invitation' => __('This business is suspended.')]);
            }

            $user = $this->resolveUser($invitation, $currentUser, $account);

            if ($user->memberships()->where('business_id', $business->getKey())->exists()) {
                throw ValidationException::withMessages(['invitation' => __('You are already a member of this business.')]);
            }

            $outletIds = $this->assignedOutletIds($invitation);

            return $this->audit->as('accepted', null, function () use ($invitation, $business, $user, $outletIds): Membership {
                $membership = $business->memberships()->create(['user_id' => $user->getKey(), 'role' => $invitation->role]);
                $membership->outlets()->attach($outletIds);

                $invitation->forceFill(['accepted_at' => now(), 'accepted_by_id' => $user->getKey()])->save();

                return $membership;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function decline(Invitation $invitation): Invitation
    {
        return DB::transaction(function () use ($invitation): Invitation {
            $invitation = $invitation->lockedForUpdate();
            $this->ensurePending($invitation);

            return $this->audit->as('declined', null, function () use ($invitation): Invitation {
                $invitation->forceFill(['declined_at' => now()])->save();

                return $invitation;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    protected function ensurePending(Invitation $invitation): void
    {
        $message = match ($invitation->status()) {
            InvitationStatus::Pending => null,
            InvitationStatus::Expired => __('This invitation has expired. Ask the business to send a new one.'),
            default => __('This invitation is no longer available.'),
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['invitation' => $message]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function resolveUser(Invitation $invitation, ?User $currentUser, ?NewAccountData $account): User
    {
        $existing = User::where('email', $invitation->email)->first();

        if ($existing === null && $currentUser === null) {
            return $this->createUser($invitation, $account);
        }

        if ($existing === null || $currentUser === null || $currentUser->isNot($existing)) {
            throw ValidationException::withMessages(['invitation' => __('This invitation is for :email. Log in with that email to accept it.', [
                'email' => $invitation->email,
            ])]);
        }

        if ($existing->isSuspended()) {
            throw ValidationException::withMessages(['invitation' => __('This account is suspended.')]);
        }

        if (! $existing->hasVerifiedEmail()) {
            $existing->markEmailAsVerified();
        }

        return $existing;
    }

    /**
     * @throws ValidationException
     */
    protected function createUser(Invitation $invitation, ?NewAccountData $account): User
    {
        if ($account === null) {
            throw ValidationException::withMessages(['name' => __('Enter your name and a password to create your login.')]);
        }

        $user = new User(['name' => $account->name, 'email' => $invitation->email, 'password' => $account->password]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    /**
     * The invitation's outlets, if they can still be worked at. Owners get none.
     *
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    protected function assignedOutletIds(Invitation $invitation): array
    {
        if ($invitation->role->coversAllOutlets()) {
            return [];
        }

        $outlets = $invitation->outlets()->get();

        if ($outlets->isEmpty() || $outlets->contains(fn (Outlet $outlet): bool => ! $outlet->isOperational())) {
            throw ValidationException::withMessages([
                'invitation' => __('An outlet in this invitation is no longer available. Ask the business to send a new one.'),
            ]);
        }

        return $outlets->map(fn (Outlet $outlet): string => $outlet->id)->all();
    }
}
