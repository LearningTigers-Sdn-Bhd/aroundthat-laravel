<?php

namespace App\Actions\Outlets;

use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hide an outlet from the public listing without suspending it, or show it again (admin).
 * A hidden outlet still trades; visitors just cannot find it, and its owner cannot list it again.
 */
class ChangeOutletVisibility
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function hide(Outlet $outlet, string $reason): Outlet
    {
        return DB::transaction(function () use ($outlet, $reason): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if ($outlet->isHidden()) {
                throw ValidationException::withMessages(['outlet' => __('This outlet is already hidden.')]);
            }

            return $this->audit->as('hidden', $reason, function () use ($outlet, $reason): Outlet {
                $outlet->forceFill(['hidden_at' => now(), 'hidden_reason' => $reason])->save();

                return $outlet;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function unhide(Outlet $outlet): Outlet
    {
        return DB::transaction(function () use ($outlet): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if (! $outlet->isHidden()) {
                throw ValidationException::withMessages(['outlet' => __('This outlet is not hidden.')]);
            }

            return $this->audit->as('unhidden', null, function () use ($outlet): Outlet {
                $outlet->forceFill(['hidden_at' => null, 'hidden_reason' => null])->save();

                return $outlet;
            });
        });
    }
}
