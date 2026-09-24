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
 * Actions that record `['field' => ['old' => …, 'new' => …]]` properties show them as changes too.
 * The `restore` property holds ids for reverting a change and is not shown.
 * Load `causer` first to avoid a query per entry.
 */
#[MapName(SnakeCaseMapper::class)]
class ActivityData extends Data
{
    /**
     * @param  list<ActivityChangeData>  $changes
     * @param  array<string, mixed>  $properties  Other details the action recorded.
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
            properties: array_filter(self::shownProperties($activity), fn (mixed $value): bool => ! self::isChange($value)),
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

        $fieldChanges = array_map(
            fn (string $field): ActivityChangeData => new ActivityChangeData($field, $old[$field] ?? null, $new[$field] ?? null),
            array_keys($new + $old),
        );

        $recordedChanges = collect(self::shownProperties($activity))
            ->filter(fn (mixed $value): bool => self::isChange($value))
            ->map(fn (array $change, string $field): ActivityChangeData => new ActivityChangeData($field, $change['old'], $change['new']))
            ->values()
            ->all();

        return [...$fieldChanges, ...$recordedChanges];
    }

    /**
     * The recorded properties, without the ids kept for reverting.
     *
     * @return array<string, mixed>
     */
    protected static function shownProperties(Activity $activity): array
    {
        return collect($activity->properties?->all() ?? [])->except('restore')->all();
    }

    /**
     * Whether a recorded property is itself a change, such as `['outlets' => ['old' => [...], 'new' => [...]]]`.
     */
    protected static function isChange(mixed $value): bool
    {
        return is_array($value) && count($value) === 2 && array_key_exists('old', $value) && array_key_exists('new', $value);
    }
}
