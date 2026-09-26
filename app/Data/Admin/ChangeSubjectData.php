<?php

namespace App\Data\Admin;

use App\Models\Business;
use App\Models\Outlet;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The outlet or business a content change was made to. Load an outlet's `business` first.
 */
#[MapName(SnakeCaseMapper::class)]
class ChangeSubjectData extends Data
{
    public function __construct(
        public string $type,
        public string $id,
        public string $name,
        public ?string $businessName,
    ) {}

    public static function fromModel(Outlet|Business $subject): self
    {
        return new self(
            type: $subject->getMorphClass(),
            id: $subject->id,
            name: $subject->name,
            businessName: $subject instanceof Outlet ? $subject->business->name : null,
        );
    }
}
