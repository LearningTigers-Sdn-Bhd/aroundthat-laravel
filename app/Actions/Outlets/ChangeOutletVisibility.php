<?php

namespace App\Actions\Outlets;

use App\Jobs\NotifyPlaceModeration;
use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hide an outlet from the public listing without suspending it, or show it again (admin).
 * A hidden outlet still trades; visitors just cannot find it, and its owner cannot list it again. Owners are emailed.
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

            $this->audit->as('hidden', $reason, fn (): bool => $outlet->forceFill(['hidden_at' => now(), 'hidden_reason' => $reason])->save());

            NotifyPlaceModeration::dispatch($outlet, 'hidden', $reason)->afterCommit();

            return $outlet;
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

            $this->audit->as('unhidden', null, fn (): bool => $outlet->forceFill(['hidden_at' => null, 'hidden_reason' => null])->save());

            NotifyPlaceModeration::dispatch($outlet, 'unhidden')->afterCommit();

            return $outlet;
        });
    }
}
