<?php

namespace App\Actions\Staff;

use App\Data\Forms\InviteStaffData;
use App\Jobs\SendStaffInvitation;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use App\Support\AssignableOutlets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Invite someone by email to join a business. The link is emailed once the invitation is saved.
 */
class InviteStaff
{
    public function __construct(
        protected AuditTrail $audit,
        protected AssignableOutlets $assignableOutlets,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $inviter, Business $business, InviteStaffData $data): Invitation
    {
        $email = Str::lower(trim($data->email));

        [$invitation, $token] = DB::transaction(function () use ($inviter, $business, $data, $email): array {
            $business = $business->lockedForUpdate();

            if ($business->isSuspended()) {
                throw ValidationException::withMessages(['business' => __('This business is suspended.')]);
            }

            if ($business->memberships()->whereHas('user', fn ($user) => $user->where('email', $email))->exists()) {
                throw ValidationException::withMessages(['email' => __('This person is already a member of the business.')]);
            }

            $outletIds = $data->role->coversAllOutlets() ? [] : $this->assignableOutlets->resolve($business, $data->outletIds);

            $this->closeExpiredInvitation($business, $email);

            return $this->audit->as('invited', null, function () use ($inviter, $business, $data, $email, $outletIds): array {
                $invitation = $business->invitations()->make(['email' => $email, 'role' => $data->role]);
                $invitation->invited_by_id = $inviter->getKey();
                $token = $invitation->issueToken();
                $invitation->save();
                $invitation->outlets()->attach($outletIds);

                return [$invitation, $token];
            });
        });

        SendStaffInvitation::dispatch($invitation, $token)->afterCommit();

        return $invitation;
    }

    /**
     * An expired invitation to the same email is replaced. A pending one must be resent or cancelled instead.
     *
     * @throws ValidationException
     */
    protected function closeExpiredInvitation(Business $business, string $email): void
    {
        $open = $business->invitations()->open()->whereRaw('lower(email) = ?', [$email])->lockForUpdate()->first();

        if ($open === null) {
            return;
        }

        if ($open->isPending()) {
            throw ValidationException::withMessages([
                'email' => __('This email already has a pending invitation. Resend or cancel it instead.'),
            ]);
        }

        $this->audit->as('replaced', null, fn () => $open->forceFill(['cancelled_at' => now()])->save());
    }
}
