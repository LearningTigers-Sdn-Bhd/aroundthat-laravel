<?php

namespace App\Jobs;

use App\Enums\Ability;
use App\Mail\PlaceModerationMail;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Email the members who manage a place's public content that an admin reverted a change or hid or unhid it.
 */
class NotifyPlaceModeration implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  'reverted'|'hidden'|'unhidden'  $action
     */
    public function __construct(
        public Outlet|Business $place,
        public string $action,
        public ?string $reason = null,
        public ?string $changeEvent = null,
    ) {}

    public function handle(): void
    {
        $business = $this->place instanceof Outlet ? $this->place->business : $this->place;

        $business->memberships()
            ->active()
            ->whereHas('user', fn (Builder $user) => $user->whereNull('suspended_at'))
            ->with('user')
            ->get()
            ->filter(fn (Membership $membership): bool => $membership->can(Ability::ManagePublicContent)
                && (! $this->place instanceof Outlet || $membership->canAccessOutlet($this->place)))
            ->each(fn (Membership $membership) => Mail::to($membership->user)
                ->send(new PlaceModerationMail($this->place, $this->action, $this->reason, $this->changeEvent)));
    }
}
