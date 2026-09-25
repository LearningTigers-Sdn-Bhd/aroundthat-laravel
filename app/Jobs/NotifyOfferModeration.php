<?php

namespace App\Jobs;

use App\Enums\Ability;
use App\Mail\OfferModerationMail;
use App\Models\Membership;
use App\Models\VoucherOffer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Email the members who manage a business's offers that an admin hid or restored one.
 */
class NotifyOfferModeration implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  'hidden'|'unhidden'  $action
     */
    public function __construct(
        public VoucherOffer $offer,
        public string $action,
        public ?string $reason = null,
    ) {}

    public function handle(): void
    {
        $this->offer->business->memberships()
            ->active()
            ->whereHas('user', fn (Builder $user) => $user->whereNull('suspended_at'))
            ->with('user')
            ->get()
            ->filter(fn (Membership $membership): bool => $membership->can(Ability::ManageOffers))
            ->each(fn (Membership $membership) => Mail::to($membership->user)
                ->send(new OfferModerationMail($this->offer, $this->action, $this->reason)));
    }
}
