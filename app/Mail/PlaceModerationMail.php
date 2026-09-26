<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells an owner an admin reverted a change to their public page, or hid or unhid their outlet.
 * Sent by the NotifyPlaceModeration job.
 */
class PlaceModerationMail extends Mailable
{
    /**
     * @param  'reverted'|'hidden'|'unhidden'  $action
     * @param  string|null  $changeEvent  The event of the change that was reverted, such as `tags_changed`.
     */
    public function __construct(
        public Outlet|Business $place,
        public string $action,
        public ?string $reason = null,
        public ?string $changeEvent = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->action) {
                'reverted' => __('A change to :place was reverted', ['place' => $this->place->name]),
                'hidden' => __(':place is hidden from visitors', ['place' => $this->place->name]),
                default => __(':place is visible to visitors again', ['place' => $this->place->name]),
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.place-moderation',
            with: [
                'placeName' => $this->place->name,
                'action' => $this->action,
                'reason' => $this->reason,
                'change' => $this->changeLabel(),
                'url' => $this->place instanceof Outlet ? route('outlets.preview', $this->place) : route('business.edit'),
            ],
        );
    }

    /**
     * The reverted change as owners know it, such as "the tags".
     */
    protected function changeLabel(): string
    {
        return match ($this->changeEvent) {
            'details_changed' => __('the details'),
            'public_profile_changed' => __('the public page'),
            'category_changed' => __('the category'),
            'tags_changed' => __('the tags'),
            'hours_changed' => __('the weekly hours'),
            'date_exceptions_changed' => __('the special dates'),
            'image_added', 'image_changed', 'image_removed', 'images_reordered' => __('the photos'),
            default => __('the public page'),
        };
    }
}
