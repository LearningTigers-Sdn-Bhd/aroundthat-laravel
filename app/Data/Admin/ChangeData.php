<?php

namespace App\Data\Admin;

use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An owner's edit to public content as admins review it in the change feed.
 * Load `causer`, `reviewedBy` and `subject` (with an outlet's `business`) first.
 */
#[MapName(SnakeCaseMapper::class)]
class ChangeData extends Data
{
    public function __construct(
        public ActivityData $activity,
        public ?ChangeSubjectData $subject,
        public ?string $reviewedByName,
        public ?CarbonInterface $revertedAt,
    ) {}

    public static function fromModel(Activity $change): self
    {
        $subject = $change->subject;

        return new self(
            activity: ActivityData::fromModel($change),
            subject: $subject instanceof Outlet || $subject instanceof Business ? ChangeSubjectData::fromModel($subject) : null,
            reviewedByName: $change->reviewedBy?->name,
            revertedAt: $change->reverted_at,
        );
    }
}
