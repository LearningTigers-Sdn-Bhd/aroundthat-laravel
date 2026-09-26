<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * One entry in the change history: who changed what, from what, to what, and why.
 *
 * @property string|null $subject_id
 * @property string|null $causer_id
 * @property string|null $reason
 * @property string|null $ip_address
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $reviewed_by_id
 * @property CarbonImmutable|null $reverted_at
 * @property int|null $reverted_by_activity_id
 */
class Activity extends BaseActivity
{
    /**
     * The log of owners' edits to what visitors see. Admins review these changes and can revert them.
     */
    public const string CONTENT_LOG = 'content';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'reviewed_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }

    /**
     * The admin who marked this change as reviewed.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * The entry that undid this change, when an admin reverted it.
     *
     * @return BelongsTo<Activity, $this>
     */
    public function revertedBy(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'reverted_by_activity_id');
    }

    public function isContent(): bool
    {
        return $this->log_name === self::CONTENT_LOG;
    }

    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }

    /**
     * Only owners' edits to what visitors see, which admins review.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function content(Builder $query): void
    {
        $query->where('log_name', self::CONTENT_LOG);
    }

    /**
     * Only changes no admin has reviewed yet.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function unreviewed(Builder $query): void
    {
        $query->whereNull('reviewed_at');
    }
}
