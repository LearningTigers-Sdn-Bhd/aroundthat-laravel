<?php

namespace App\Data;

use App\Enums\ImageKind;
use App\Models\Business;
use App\Models\Image;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What visitors see about a business across its outlets.
 */
#[MapName(SnakeCaseMapper::class)]
class BusinessPlaceData extends Data
{
    public function __construct(
        public string $slug,
        public ?string $summary,
        public ?string $description,
        public ?ImageData $logo,
    ) {}

    public static function fromModel(Business $business): self
    {
        /** @var Image|null $logo */
        $logo = $business->images()->active()->where('kind', ImageKind::Logo)->first();

        return new self(
            slug: $business->slug,
            summary: $business->summary,
            description: $business->description,
            logo: $logo ? ImageData::fromModel($logo) : null,
        );
    }
}
