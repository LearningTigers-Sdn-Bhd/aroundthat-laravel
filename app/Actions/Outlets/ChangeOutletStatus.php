<?php

namespace App\Actions\Outlets;

use App\Models\Outlet;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Suspend or reactivate an outlet (admin), and archive or restore it (owner or admin).
 */
class ChangeOutletStatus
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function suspend(User $admin, Outlet $outlet, string $reason): Outlet
    {
        return $this->change($outlet, 'suspended', $reason,
            fn (Outlet $outlet): ?string => match (true) {
                $outlet->isArchived() => 'Archived outlets cannot be suspended.',
                $outlet->isSuspended() => 'This outlet is already suspended.',
                default => null,
            },
            ['suspended_at' => now(), 'suspended_by_id' => $admin->getKey(), 'suspension_reason' => $reason],
        );
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Outlet $outlet): Outlet
    {
        return $this->change($outlet, 'reactivated', null,
            fn (Outlet $outlet): ?string => match (true) {
                $outlet->isArchived() => 'Restore the outlet before reactivating it.',
                ! $outlet->isSuspended() => 'This outlet is not suspended.',
                default => null,
            },
            ['suspended_at' => null, 'suspended_by_id' => null, 'suspension_reason' => null],
        );
    }

    /**
     * @throws ValidationException
     */
    public function archive(Outlet $outlet): Outlet
    {
        return $this->change($outlet, 'archived', null,
            fn (Outlet $outlet): ?string => $outlet->isArchived() ? 'This outlet is already archived.' : null,
            ['archived_at' => now()],
        );
    }

    /**
     * @throws ValidationException
     */
    public function restore(Outlet $outlet): Outlet
    {
        return $this->change($outlet, 'restored', null,
            fn (Outlet $outlet): ?string => $outlet->isArchived() ? null : 'This outlet is not archived.',
            ['archived_at' => null],
        );
    }

    /**
     * @param  Closure(Outlet): ?string  $refusal  Returns why the change is not allowed (untranslated), or null.
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    protected function change(Outlet $outlet, string $event, ?string $reason, Closure $refusal, array $attributes): Outlet
    {
        return DB::transaction(function () use ($outlet, $event, $reason, $refusal, $attributes): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if (($message = $refusal($outlet)) !== null) {
                throw ValidationException::withMessages(['outlet' => __($message)]);
            }

            return $this->audit->as($event, $reason, function () use ($outlet, $attributes): Outlet {
                $outlet->forceFill($attributes)->save();

                return $outlet;
            });
        });
    }
}
