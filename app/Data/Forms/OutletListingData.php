<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Whether the owner wants visitors to find the outlet.
 */
#[MapName(SnakeCaseMapper::class)]
class OutletListingData extends Data
{
    public function __construct(
        public bool $isListed = false,
    ) {}
}
