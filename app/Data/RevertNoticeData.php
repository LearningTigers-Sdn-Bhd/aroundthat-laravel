<?php

namespace App\Data;

use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A change to a place that an admin recently reverted, as its owners are told about it.
 */
#[MapName(SnakeCaseMapper::class)]
class RevertNoticeData extends Data
{
    public const int SHOWN_FOR_DAYS = 30;

    public function __construct(
        public string $event,
        public ?string $reason,
        public ?CarbonInterface $revertedAt,
    ) {}

    /**
     * The changes to the place reverted in the last SHOWN_FOR_DAYS days, newest first.
     *
     * @return list<self>
     */
    public static function recentFor(Outlet|Business $place): array
    {
        return array_values(Activity::forSubject($place)
            ->content()
            ->where('event', 'reverted')
            ->where('created_at', '>=', now()->subDays(self::SHOWN_FOR_DAYS))
            ->latest('id')
            ->limit(20)
            ->get()
            ->unique(fn (Activity $revert): mixed => $revert->properties?->get('reverts')['id'] ?? $revert->id)
            ->take(5)
            ->map(fn (Activity $revert): self => new self(
                event: $revert->properties?->get('reverts')['event'] ?? 'reverted',
                reason: $revert->reason,
                revertedAt: $revert->created_at,
            ))
            ->all());
    }
}
