<?php

namespace App\Actions\Businesses;

use App\Models\Business;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin suspends a business, or lifts the suspension.
 */
class SuspendBusiness
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function suspend(User $admin, Business $business, string $reason): Business
    {
        return $this->change($business, 'suspended', $reason, true, [
            'suspended_at' => now(),
            'suspended_by_id' => $admin->getKey(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Business $business): Business
    {
        return $this->change($business, 'reactivated', null, false, [
            'suspended_at' => null,
            'suspended_by_id' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    protected function change(Business $business, string $event, ?string $reason, bool $suspending, array $attributes): Business
    {
        return DB::transaction(function () use ($business, $event, $reason, $suspending, $attributes): Business {
            $business = $business->lockedForUpdate();

            if ($business->isSuspended() === $suspending) {
                throw ValidationException::withMessages([
                    'business' => $suspending ? __('This business is already suspended.') : __('This business is not suspended.'),
                ]);
            }

            return $this->audit->as($event, $reason, function () use ($business, $attributes): Business {
                $business->forceFill($attributes)->save();

                return $business;
            });
        });
    }
}
