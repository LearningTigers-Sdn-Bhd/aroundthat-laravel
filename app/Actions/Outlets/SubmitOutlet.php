<?php

namespace App\Actions\Outlets;

use App\Enums\OnboardingStatus;
use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The owner sends a draft or rejected outlet to admin review, once its business is approved.
 */
class SubmitOutlet
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Outlet $outlet): Outlet
    {
        return DB::transaction(function () use ($outlet): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if (! $outlet->onboarding_status->canBeSubmitted() || ! $outlet->isWritable()) {
                throw ValidationException::withMessages([
                    'outlet' => __('Only a draft or rejected outlet can be submitted for review.'),
                ]);
            }

            if (! $outlet->business->isApproved()) {
                throw ValidationException::withMessages([
                    'outlet' => __('The business must be approved before its outlets can be submitted.'),
                ]);
            }

            return $this->audit->as('submitted', null, function () use ($outlet): Outlet {
                $outlet->forceFill([
                    'onboarding_status' => OnboardingStatus::Pending,
                    'submitted_at' => now(),
                    'rejection_reason' => null,
                ])->save();

                return $outlet;
            });
        });
    }
}
