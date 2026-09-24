<?php

namespace App\Data;

use App\Models\Outlet;
use Spatie\LaravelData\Data;

/**
 * An outlet named in a list: a member's outlets, an invitation's outlets, a select option.
 */
class OutletOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromModel(Outlet $outlet): self
    {
        return new self(id: $outlet->id, name: $outlet->name);
    }
}
