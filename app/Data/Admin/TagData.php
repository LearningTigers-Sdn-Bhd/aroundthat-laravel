<?php

namespace App\Data\Admin;

use App\Enums\TagStatus;
use App\Models\Tag;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A tag as admins review it. Load `createdByBusiness`, `mergedInto` and `outlets_count` first.
 */
#[MapName(SnakeCaseMapper::class)]
class TagData extends Data
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public TagStatus $status,
        public bool $isActive,
        public ?string $createdByBusinessId,
        public ?string $createdByBusinessName,
        public ?string $mergedIntoName,
        public int $outletsCount,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Tag $tag): self
    {
        return new self(
            id: $tag->id,
            slug: $tag->slug,
            name: $tag->name,
            status: $tag->status,
            isActive: $tag->is_active,
            createdByBusinessId: $tag->created_by_business_id,
            createdByBusinessName: $tag->createdByBusiness?->name,
            mergedIntoName: $tag->mergedInto?->name,
            outletsCount: (int) $tag->getAttribute('outlets_count'),
            createdAt: $tag->created_at,
        );
    }
}
