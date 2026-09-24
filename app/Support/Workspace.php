<?php

namespace App\Support;

use App\Enums\Ability;
use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use LogicException;

/**
 * The business the signed-in user is working in for this request, set by ResolveCurrentBusiness.
 */
class Workspace
{
    protected ?Membership $membership = null;

    public function set(Membership $membership): void
    {
        $this->membership = $membership;
    }

    public function isResolved(): bool
    {
        return $this->membership !== null;
    }

    public function membership(): Membership
    {
        return $this->membership ?? throw new LogicException('No workspace was resolved for this request.');
    }

    public function business(): Business
    {
        return $this->membership()->business;
    }

    /**
     * Stop with a 403 unless the member's role in this business allows the ability.
     */
    public function authorize(Ability $ability): void
    {
        abort_unless($this->membership()->can($ability), 403);
    }

    /**
     * Stop with a 404 when a record from the URL belongs to another business, even one the user also works in.
     */
    public function ensureOwns(Outlet|Membership|Invitation $record): void
    {
        abort_unless($record->business_id === $this->membership()->business_id, 404);
    }
}
