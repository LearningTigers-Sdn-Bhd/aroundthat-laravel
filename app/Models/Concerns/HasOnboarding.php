<?php

namespace App\Models\Concerns;

use App\Enums\OnboardingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One-time admin approval and admin suspension, shared by businesses and outlets.
 *
 * @property OnboardingStatus $onboarding_status
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property string|null $approved_by_id
 * @property string|null $rejection_reason
 * @property Carbon|null $suspended_at
 * @property string|null $suspended_by_id
 * @property string|null $suspension_reason
 */
trait HasOnboarding
{
    /**
     * Register the onboarding and suspension casts, and start new records as drafts.
     */
    public function initializeHasOnboarding(): void
    {
        $this->attributes['onboarding_status'] ??= OnboardingStatus::Draft->value;

        $this->mergeCasts([
            'onboarding_status' => OnboardingStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ]);
    }

    /**
     * The admin who approved it.
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * The admin who suspended it.
     *
     * @return BelongsTo<User, $this>
     */
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_id');
    }

    public function isApproved(): bool
    {
        return $this->onboarding_status === OnboardingStatus::Approved;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Only records an admin has approved.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where($this->qualifyColumn('onboarding_status'), OnboardingStatus::Approved);
    }

    /**
     * Only records that are not suspended.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function notSuspended(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('suspended_at'));
    }
}
