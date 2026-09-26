<?php

namespace App\Http\Resources;

use App\Enums\ImageKind;
use App\Models\Image;
use App\Models\Outlet;
use App\Support\OpeningHours;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A public outlet in the outlet list. Load `category`, `business`, the public `tags`, active `images`
 * and upcoming `dateExceptions` first.
 *
 * @mixin Outlet
 */
class OutletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $openState = OpeningHours::openState($this->resource, now());
        /** @var Image|null $cover */
        $cover = $this->images->first(fn (Image $image): bool => $image->kind === ImageKind::Cover);

        return [
            /** Permanent; use it to fetch the outlet and to send engagement events. */
            'slug' => $this->slug,
            'name' => $this->name,
            'summary' => $this->summary,
            'category' => new CategoryResource($this->category),
            'tags' => TagResource::collection($this->tags),
            'address' => [
                'line_1' => $this->address_line_1,
                'line_2' => $this->address_line_2,
                'postcode' => $this->postcode,
                'city' => $this->city,
                'state' => $this->state,
                'state_slug' => Str::slug($this->state),
                'country_code' => $this->country_code,
            ],
            'location' => [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ],
            'timezone' => $this->timezone,
            'cover_image' => $cover ? new ImageResource($cover) : null,
            /** Whether it is open at the time of the request, in its own timezone, special dates included. */
            'open_now' => [
                'is_open' => $openState['is_open'],
                /** Local HH:MM it closes, while open. */
                'closes_at' => $openState['closes_at'],
                /** Local HH:MM it opens again later today, while closed. */
                'next_opens_at' => $openState['next_opens_at'],
            ],
            'business' => [
                'slug' => $this->business->slug,
                'name' => $this->business->name,
            ],
            /** Changes to the outlet, its tags, photos and special dates. Business details do not change it. */
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
