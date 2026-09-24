<?php

namespace App\Data\Admin;

use App\Models\Activity;
use App\Models\User;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One entry in a change history: what happened, who did it, why, and each field's old and new value.
 * Load `causer` first to avoid a query per entry.
 */
#[MapName(SnakeCaseMapper::class)]
class ActivityData extends Data
{
    /**
     * @param  list<ActivityChangeData>  $changes
     * @param  array<string, mixed>  $properties  Details of actions that change no fields, such as the old and new outlets.
     */
    public function __construct(
        public int $id,
        public string $event,
        public ?string $subjectType,
        public ?string $subjectId,
        public ?string $causerName,
        public ?string $reason,
        public ?string $ipAddress,
        public array $changes,
        public array $properties,
        public ?CarbonInterface $reviewedAt,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        $causer = $activity->causer;

        return new self(
            id: $activity->id,
            event: $activity->event ?? $activity->description,
            subjectType: $activity->subject_type,
            subjectId: $activity->subject_id,
            causerName: $causer instanceof User ? $causer->name : null,
            reason: $activity->reason,
            ipAddress: $activity->ip_address,
            changes: self::changes($activity),
            properties: $activity->properties?->all() ?? [],
            reviewedAt: $activity->reviewed_at,
            createdAt: $activity->created_at,
        );
    }

    /**
     * @return list<ActivityChangeData>
     */
    protected static function changes(Activity $activity): array
    {
        $new = $activity->attribute_changes?->get('attributes', []) ?? [];
        $old = $activity->attribute_changes?->get('old', []) ?? [];

        return array_map(
            fn (string $field): ActivityChangeData => new ActivityChangeData($field, $old[$field] ?? null, $new[$field] ?? null),
            array_keys($new + $old),
        );
    }
}
