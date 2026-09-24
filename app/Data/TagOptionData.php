<?php

namespace App\Data;

use App\Models\Tag;
use Spatie\LaravelData\Data;

/**
 * A tag as owners see it. It has no review status on purpose: owners are never told a tag waits for an admin.
 */
class TagOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromModel(Tag $tag): self
    {
        return new self(id: $tag->id, name: $tag->name);
    }
}
