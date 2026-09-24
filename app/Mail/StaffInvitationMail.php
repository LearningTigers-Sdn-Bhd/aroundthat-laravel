<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The email that carries an invitation's link. Sent by the SendStaffInvitation job.
 */
class StaffInvitationMail extends Mailable
{
    public function __construct(
        public Invitation $invitation,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You are invited to :business', ['business' => $this->invitation->business->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.staff-invitation',
            with: [
                'businessName' => $this->invitation->business->name,
                'inviterName' => $this->invitation->invitedBy?->name,
                'role' => $this->invitation->role->value,
                'url' => route('invitations.show', $this->token),
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
