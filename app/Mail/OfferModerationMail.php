<?php

namespace App\Mail;

use App\Models\VoucherOffer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a business's offer managers that an admin hid or restored one of its offers.
 */
class OfferModerationMail extends Mailable
{
    /**
     * @param  'hidden'|'unhidden'  $action
     */
    public function __construct(
        public VoucherOffer $offer,
        public string $action,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->action === 'hidden'
                ? __('Your offer :offer was taken down', ['offer' => $this->offer->name])
                : __('Your offer :offer can run again', ['offer' => $this->offer->name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.offer-moderation',
            with: [
                'offerName' => $this->offer->name,
                'action' => $this->action,
                'reason' => $this->reason,
                'url' => route('offers.edit', $this->offer),
            ],
        );
    }
}
