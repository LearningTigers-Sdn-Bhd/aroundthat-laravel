<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What visitors see about a business across its outlets. Its name and contacts come from the business details.
 */
#[MapName(SnakeCaseMapper::class)]
class BusinessPublicProfileData extends Data
{
    public function __construct(
        #[Max(280)]
        public ?string $summary = null,
        #[Max(5000)]
        public ?string $description = null,
    ) {}
}
