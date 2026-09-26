<?php

namespace App\Data\Forms;

use App\Enums\MediaDisk;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Where an admin wants new images stored.
 */
#[MapName(SnakeCaseMapper::class)]
class MediaSettingsData extends Data
{
    public function __construct(
        public MediaDisk $mediaDisk,
    ) {}
}
