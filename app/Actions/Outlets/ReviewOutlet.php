<?php

namespace App\Actions\Outlets;

use App\Enums\OnboardingStatus;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin approves or rejects an outlet that is waiting for review.
 */
class ReviewOutlet
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function approve(User $admin, Outlet $outlet): Outlet
    {
        return $this->review($outlet, 'approved', null, [
            'onboarding_status' => OnboardingStatus::Approved,
            'approved_at' => now(),
            'approved_by_id' => $admin->getKey(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function reject(Outlet $outlet, string $reason): Outlet
    {
        return $this->review($outlet, 'rejected', $reason, [
            'onboarding_status' => OnboardingStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    protected function review(Outlet $outlet, string $event, ?string $reason, array $attributes): Outlet
    {
        return DB::transaction(function () use ($outlet, $event, $reason, $attributes): Outlet {
            $outlet->business->lockedForUpdate();
            $outlet = $outlet->lockedForUpdate();

            if ($outlet->onboarding_status !== OnboardingStatus::Pending) {
                throw ValidationException::withMessages([
                    'outlet' => __('Only an outlet waiting for review can be approved or rejected.'),
                ]);
            }

            if ($event === 'approved' && ! $outlet->business->isApproved()) {
                throw ValidationException::withMessages([
                    'outlet' => __('Approve the business before approving its outlets.'),
                ]);
            }

            return $this->audit->as($event, $reason, function () use ($outlet, $attributes): Outlet {
                $outlet->forceFill($attributes)->save();

                return $outlet;
            });
        });
    }
}
