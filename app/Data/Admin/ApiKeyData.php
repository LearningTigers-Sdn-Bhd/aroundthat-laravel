<?php

namespace App\Data\Admin;

use Carbon\CarbonInterface;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One of an integration's API keys. The key itself is never shown again after it is created.
 */
#[MapName(SnakeCaseMapper::class)]
class ApiKeyData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?CarbonInterface $lastUsedAt,
        public ?CarbonInterface $expiresAt,
        public bool $isExpired,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(PersonalAccessToken $token): self
    {
        return new self(
            id: $token->id,
            name: $token->name,
            lastUsedAt: $token->last_used_at,
            expiresAt: $token->expires_at,
            isExpired: $token->expires_at !== null && $token->expires_at->isPast(),
            createdAt: $token->created_at,
        );
    }
}
