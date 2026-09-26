<?php

namespace App\Models\Concerns;

trait LocksForUpdate
{
    /**
     * Reload this row with a lock held until the surrounding transaction ends.
     */
    public function lockedForUpdate(): static
    {
        return static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
    }
}
