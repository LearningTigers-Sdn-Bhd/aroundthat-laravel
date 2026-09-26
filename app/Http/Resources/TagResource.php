<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A tag in the partner API. `slug` is permanent and is what filters take.
 *
 * @mixin Tag
 */
class TagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            /** The number of public outlets with this tag. Only in the tag list. */
            'outlet_count' => $this->whenHas('outlet_count', fn (): int => (int) $this->getAttribute('outlet_count')),
        ];
    }
}
