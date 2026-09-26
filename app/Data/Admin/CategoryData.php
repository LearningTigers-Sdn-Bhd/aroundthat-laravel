<?php

namespace App\Data\Admin;

use App\Models\Category;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A category as admins manage it. Load `outlets_count` first.
 */
#[MapName(SnakeCaseMapper::class)]
class CategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public int $position,
        public bool $isActive,
        public int $outletsCount,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            slug: $category->slug,
            name: $category->name,
            position: $category->position,
            isActive: $category->is_active,
            outletsCount: (int) $category->getAttribute('outlets_count'),
        );
    }
}
