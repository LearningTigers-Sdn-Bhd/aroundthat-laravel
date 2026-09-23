<?php

namespace App\Actions\Businesses;

use App\Enums\OnboardingStatus;
use App\Models\Business;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin approves or rejects a business that is waiting for review.
 */
class ReviewBusiness
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function approve(User $admin, Business $business): Business
    {
        return $this->review($business, 'approved', null, [
            'onboarding_status' => OnboardingStatus::Approved,
            'approved_at' => now(),
            'approved_by_id' => $admin->getKey(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function reject(Business $business, string $reason): Business
    {
        return $this->review($business, 'rejected', $reason, [
            'onboarding_status' => OnboardingStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    protected function review(Business $business, string $event, ?string $reason, array $attributes): Business
    {
        return DB::transaction(function () use ($business, $event, $reason, $attributes): Business {
            $business = $business->lockedForUpdate();

            if ($business->onboarding_status !== OnboardingStatus::Pending) {
                throw ValidationException::withMessages([
                    'business' => __('Only a business waiting for review can be approved or rejected.'),
                ]);
            }

            return $this->audit->as($event, $reason, function () use ($business, $attributes): Business {
                $business->forceFill($attributes)->save();

                return $business;
            });
        });
    }
}
