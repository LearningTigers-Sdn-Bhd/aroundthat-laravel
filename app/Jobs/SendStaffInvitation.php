<?php

namespace App\Jobs;

use App\Mail\StaffInvitationMail;
use App\Models\Invitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Email an invitation's link and record when it went out.
 */
class SendStaffInvitation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Invitation $invitation,
        public string $token,
    ) {}

    /**
     * Skip links that were cancelled, answered or replaced by a resend after the job was queued.
     */
    public function handle(): void
    {
        if (! $this->invitation->isPending() || $this->invitation->token_hash !== Invitation::hashToken($this->token)) {
            return;
        }

        Mail::to($this->invitation->email)->send(new StaffInvitationMail($this->invitation, $this->token));

        $this->invitation->forceFill(['sent_at' => now()])->save();
    }
}
