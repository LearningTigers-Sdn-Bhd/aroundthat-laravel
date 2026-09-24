<?php

namespace App\Actions\Staff;

use App\Jobs\SendStaffInvitation;
use App\Models\Invitation;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The business's side of an invitation it sent: send a fresh link, or withdraw it.
 */
class ManageInvitation
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * Email a new link with a fresh expiry. Earlier links stop working. Works for expired invitations too.
     *
     * @throws ValidationException
     */
    public function resend(Invitation $invitation): Invitation
    {
        [$invitation, $token] = DB::transaction(function () use ($invitation): array {
            $invitation = $invitation->lockedForUpdate();
            $this->ensureOpen($invitation);

            if ($invitation->business->isSuspended()) {
                throw ValidationException::withMessages(['business' => __('This business is suspended.')]);
            }

            $token = $invitation->issueToken();
            $invitation->forceFill(['sent_at' => null])->save();
            $this->audit->record($invitation, 'resent');

            return [$invitation, $token];
        });

        SendStaffInvitation::dispatch($invitation, $token)->afterCommit();

        return $invitation;
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Invitation $invitation): Invitation
    {
        return DB::transaction(function () use ($invitation): Invitation {
            $invitation = $invitation->lockedForUpdate();
            $this->ensureOpen($invitation);

            return $this->audit->as('cancelled', null, function () use ($invitation): Invitation {
                $invitation->forceFill(['cancelled_at' => now()])->save();

                return $invitation;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    protected function ensureOpen(Invitation $invitation): void
    {
        if (! $invitation->isOpen()) {
            throw ValidationException::withMessages(['invitation' => __('This invitation was already :status.', [
                'status' => $invitation->status()->value,
            ])]);
        }
    }
}
