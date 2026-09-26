<?php

namespace App\Actions\Outlets;

use App\Data\Forms\OutletDetailsData;
use App\Enums\OnboardingStatus;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Add an outlet to a business. Owners create drafts; an admin may approve it in the same step.
 */
class CreateOutlet
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @param  User|null  $approvingAdmin  Set only from the admin area to approve the outlet at once.
     *
     * @throws ValidationException
     */
    public function handle(Business $business, OutletDetailsData $data, ?User $approvingAdmin = null): Outlet
    {
        return DB::transaction(function () use ($business, $data, $approvingAdmin): Outlet {
            $business = $business->lockedForUpdate();

            if ($business->isSuspended()) {
                throw ValidationException::withMessages([
                    'business' => __('Outlets cannot be added to a suspended business.'),
                ]);
            }

            if ($approvingAdmin && ! $business->isApproved()) {
                throw ValidationException::withMessages([
                    'approve_immediately' => __('Approve the business before approving its outlets.'),
                ]);
            }

            return $this->audit->as($approvingAdmin ? 'created_and_approved' : 'created', null, function () use ($business, $data, $approvingAdmin): Outlet {
                $outlet = $business->outlets()->make($data->toModelAttributes());

                if ($approvingAdmin) {
                    $outlet->forceFill([
                        'onboarding_status' => OnboardingStatus::Approved,
                        'approved_at' => now(),
                        'approved_by_id' => $approvingAdmin->getKey(),
                    ]);
                }

                $outlet->save();

                return $outlet;
            });
        });
    }
}
