<?php

namespace App\Actions\Staff;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Support\ActivityLog\AuditTrail;
use App\Support\AssignableOutlets;
use App\Support\EnsureBusinessKeepsAnOwner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Change a member's role and, for managers and cashiers, the outlets they work at.
 */
class UpdateMemberAccess
{
    public function __construct(
        protected AuditTrail $audit,
        protected EnsureBusinessKeepsAnOwner $ownerGuard,
        protected AssignableOutlets $assignableOutlets,
    ) {}

    /**
     * @param  array<int, string>  $outletIds  Ignored for owners, who cover every outlet.
     *
     * @throws ValidationException
     */
    public function handle(Membership $membership, MembershipRole $role, array $outletIds = []): Membership
    {
        return DB::transaction(function () use ($membership, $role, $outletIds): Membership {
            $membership->business->lockedForUpdate();
            $membership = $membership->lockedForUpdate();

            if ($membership->isOwner() && $role !== MembershipRole::Owner) {
                $this->ownerGuard->forMembership($membership);
            }

            $outletIds = $role->coversAllOutlets() ? [] : $this->assignableOutlets->resolve($membership->business, $outletIds);
            $previousOutletNames = $this->outletNames($membership);

            return $this->audit->as('access_changed', null, function () use ($membership, $role, $outletIds, $previousOutletNames): Membership {
                $membership->update(['role' => $role]);
                $membership->outlets()->sync($outletIds);

                $outletNames = $this->outletNames($membership);

                if ($previousOutletNames !== $outletNames) {
                    $this->audit->record($membership, 'outlets_changed', null, [
                        'outlets' => ['old' => $previousOutletNames, 'new' => $outletNames],
                    ]);
                }

                return $membership;
            });
        });
    }

    /**
     * The member's outlets by name, for the change log.
     *
     * @return array<int, string>
     */
    protected function outletNames(Membership $membership): array
    {
        return $membership->outlets()->orderBy('name')->pluck('name')->all();
    }
}
