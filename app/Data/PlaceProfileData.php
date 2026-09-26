<?php

namespace App\Data;

use App\Models\Outlet;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What visitors see about an outlet, beyond its name, address and contacts. Load `category`, `tags` and `business` first.
 */
#[MapName(SnakeCaseMapper::class)]
class PlaceProfileData extends Data
{
    /**
     * @param  list<TagOptionData>  $tags
     * @param  list<string>  $missingForListing
     */
    public function __construct(
        public string $slug,
        public ?string $summary,
        public ?string $description,
        public ?CategoryOptionData $category,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $googleMapsUrl,
        public ?string $website,
        public ?string $whatsapp,
        public ?string $facebook,
        public ?string $instagram,
        public array $tags,
        public bool $isListed,
        public bool $isPublic,
        public ?string $hiddenReason,
        public array $missingForListing,
    ) {}

    public static function fromModel(Outlet $outlet): self
    {
        return new self(
            slug: $outlet->slug,
            summary: $outlet->summary,
            description: $outlet->description,
            category: $outlet->category ? CategoryOptionData::fromModel($outlet->category) : null,
            latitude: $outlet->latitude === null ? null : (float) $outlet->latitude,
            longitude: $outlet->longitude === null ? null : (float) $outlet->longitude,
            googleMapsUrl: $outlet->google_maps_url,
            website: $outlet->website,
            whatsapp: $outlet->whatsapp,
            facebook: $outlet->facebook,
            instagram: $outlet->instagram,
            tags: array_values($outlet->tags->map(fn ($tag) => TagOptionData::fromModel($tag))->all()),
            isListed: $outlet->is_listed,
            isPublic: $outlet->isPublic(),
            hiddenReason: $outlet->isHidden() ? $outlet->hidden_reason : null,
            missingForListing: $outlet->missingForListing(),
        );
    }
}
