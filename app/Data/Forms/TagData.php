<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A tag an admin adds or renames. A form checkbox sends nothing when unticked, so `is_active` defaults to false.
 */
#[MapName(SnakeCaseMapper::class)]
class TagData extends Data
{
    public function __construct(
        #[Max(40)]
        public string $name,
        public bool $isActive = false,
    ) {}

    /**
     * @return array{name: string, is_active: bool}
     */
    public function toModelAttributes(): array
    {
        return [
            'name' => trim((string) preg_replace('/\s+/u', ' ', $this->name)),
            'is_active' => $this->isActive,
        ];
    }
}
