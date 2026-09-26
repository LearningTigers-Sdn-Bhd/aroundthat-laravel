<?php

namespace App\Http\Resources;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A photo or logo in the partner API.
 *
 * @mixin Image
 */
class ImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** `cover`, `gallery` or `logo`. */
            'kind' => $this->kind->value,
            'url' => $this->url(),
            'alt_text' => $this->alt_text,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
