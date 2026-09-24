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
