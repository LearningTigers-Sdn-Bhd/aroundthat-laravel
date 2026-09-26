<?php

namespace App\Data;

use App\Models\Category;
use Spatie\LaravelData\Data;

/**
 * A category an owner can pick for an outlet.
 */
class CategoryOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(id: $category->id, name: $category->name);
    }
}
