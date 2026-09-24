<?php

namespace App\Data\Admin;

use App\Models\Outlet;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An outlet an admin can choose as another outlet's host, such as a mall. Load `business` first.
 */
#[MapName(SnakeCaseMapper::class)]
class HostOutletOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $businessName,
    ) {}

    public static function fromModel(Outlet $outlet): self
    {
        return new self(id: $outlet->id, name: $outlet->name, businessName: $outlet->business->name);
    }
}
