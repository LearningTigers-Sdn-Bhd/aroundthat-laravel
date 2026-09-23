<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Membership;
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
}
