<?php

namespace App\Actions\Businesses;

use App\Enums\OnboardingStatus;
use App\Models\Business;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The owner sends a draft or rejected business to admin review.
 */
class SubmitBusiness
{
    /**
     * Fields an admin needs before they can review a business.
     */
    public const REQUIRED_FIELDS = ['name', 'registered_name', 'registration_number', 'contact_email', 'timezone'];

    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Business $business): Business
    {
        return DB::transaction(function () use ($business): Business {
            $business = $business->lockedForUpdate();

            if (! $business->onboarding_status->canBeSubmitted() || $business->isSuspended()) {
                throw ValidationException::withMessages([
                    'business' => __('Only a draft or rejected business can be submitted for review.'),
                ]);
            }

            $missing = array_filter(self::REQUIRED_FIELDS, fn (string $field): bool => blank($business->{$field}));

            if ($missing !== []) {
                throw ValidationException::withMessages(array_fill_keys(
                    array_values($missing),
                    __('Complete this before submitting for review.'),
                ));
            }

            return $this->audit->as('submitted', null, function () use ($business): Business {
                $business->forceFill([
                    'onboarding_status' => OnboardingStatus::Pending,
                    'submitted_at' => now(),
                    'rejection_reason' => null,
                ])->save();

                return $business;
            });
        });
    }
}
