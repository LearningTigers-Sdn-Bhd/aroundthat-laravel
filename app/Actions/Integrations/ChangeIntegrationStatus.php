<?php

namespace App\Actions\Integrations;

use App\Models\Integration;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin suspends or reactivates an integration. While suspended, every one of its API keys is refused.
 */
class ChangeIntegrationStatus
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function suspend(User $admin, Integration $integration, string $reason): Integration
    {
        return DB::transaction(function () use ($admin, $integration, $reason): Integration {
            $integration = $integration->lockedForUpdate();

            if ($integration->isSuspended()) {
                throw ValidationException::withMessages(['integration' => __('This integration is already suspended.')]);
            }

            return $this->audit->as('suspended', $reason, function () use ($admin, $integration, $reason): Integration {
                $integration->forceFill([
                    'suspended_at' => now(),
                    'suspended_by_id' => $admin->getKey(),
                    'suspension_reason' => $reason,
                ])->save();

                return $integration;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Integration $integration): Integration
    {
        return DB::transaction(function () use ($integration): Integration {
            $integration = $integration->lockedForUpdate();

            if (! $integration->isSuspended()) {
                throw ValidationException::withMessages(['integration' => __('This integration is not suspended.')]);
            }

            return $this->audit->as('reactivated', null, function () use ($integration): Integration {
                $integration->forceFill([
                    'suspended_at' => null,
                    'suspended_by_id' => null,
                    'suspension_reason' => null,
                ])->save();

                return $integration;
            });
        });
    }
}
