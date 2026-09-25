<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category in the partner API. `slug` is permanent and is what filters take.
 *
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            /** The number of public outlets with this category. Only in the category list. */
            'outlet_count' => $this->whenHas('outlet_count', fn (): int => (int) $this->getAttribute('outlet_count')),
        ];
    }
}
