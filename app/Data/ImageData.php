<?php

namespace App\Data;

use App\Enums\ImageKind;
use App\Models\Image;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An uploaded photo or logo, with the URL it is served from.
 */
#[MapName(SnakeCaseMapper::class)]
class ImageData extends Data
{
    public function __construct(
        public string $id,
        public ImageKind $kind,
        public string $url,
        public string $altText,
        public int $width,
        public int $height,
        public int $position,
    ) {}

    public static function fromModel(Image $image): self
    {
        return new self(
            id: $image->id,
            kind: $image->kind,
            url: $image->url(),
            altText: $image->alt_text,
            width: $image->width,
            height: $image->height,
            position: $image->position,
        );
    }
}
