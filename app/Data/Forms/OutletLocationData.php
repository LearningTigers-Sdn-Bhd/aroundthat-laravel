<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Where an outlet sits on the map. A pasted Google Maps link fills the coordinates.
 */
#[MapName(SnakeCaseMapper::class)]
class OutletLocationData extends Data
{
    public function __construct(
        #[Max(2000)]
        public ?string $googleMapsUrl = null,
        #[Between(-90, 90), RequiredWith('longitude')]
        public ?float $latitude = null,
        #[Between(-180, 180), RequiredWith('latitude')]
        public ?float $longitude = null,
    ) {}
}
