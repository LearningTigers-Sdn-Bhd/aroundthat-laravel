<?php

namespace App\Data\Admin;

use App\Enums\IntegrationType;
use App\Models\Integration;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An integration as admins see it. Load `suspendedBy` and `tokens_count` first.
 */
#[MapName(SnakeCaseMapper::class)]
class IntegrationData extends Data
{
    /**
     * @param  list<string>  $capabilities
     */
    public function __construct(
        public string $id,
        public string $name,
        public IntegrationType $type,
        public array $capabilities,
        public ?CarbonInterface $startsAt,
        public ?CarbonInterface $expiresAt,
        public bool $isUsable,
        public int $keysCount,
        public ?CarbonInterface $suspendedAt,
        public ?string $suspendedByName,
        public ?string $suspensionReason,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Integration $integration): self
    {
        return new self(
            id: $integration->id,
            name: $integration->name,
            type: $integration->type,
            capabilities: $integration->capabilities,
            startsAt: $integration->starts_at,
            expiresAt: $integration->expires_at,
            isUsable: $integration->isUsable(),
            keysCount: (int) $integration->getAttribute('tokens_count'),
            suspendedAt: $integration->suspended_at,
            suspendedByName: $integration->suspendedBy?->name,
            suspensionReason: $integration->suspension_reason,
            createdAt: $integration->created_at,
        );
    }
}
