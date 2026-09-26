<?php

namespace App\Actions\Outlets;

use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin records which outlet another one physically sits inside, such as a restaurant inside a mall.
 * This never grants the host any access to the hosted outlet or its business.
 *
 * Row locks cannot stop a cycle: one request can set A -> B while another sets B -> A, and each sees
 * no cycle. Every host change therefore takes one transaction-scoped advisory lock first.
 */
class SetHostOutlet
{
    protected const LOCK_KEY = 'outlets.host_outlet_id';

    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function assign(Outlet $outlet, Outlet $host): Outlet
    {
        return DB::transaction(function () use ($outlet, $host): Outlet {
            $this->lock();
            $outlet = $outlet->lockedForUpdate();
            $host = $host->lockedForUpdate();

            $refusal = match (true) {
                $outlet->isArchived() => __('Archived outlets cannot have a host.'),
                $host->is($outlet) => __('An outlet cannot host itself.'),
                ! $host->isApproved() || $host->isArchived() => __('The host outlet must be approved and not archived.'),
                in_array($outlet->getKey(), $this->hostChain($host), true) => __('This host would place the outlet inside itself.'),
                default => null,
            };

            if ($refusal !== null) {
                throw ValidationException::withMessages(['host_outlet_id' => $refusal]);
            }

            return $this->save($outlet, 'host_assigned', $host->getKey());
        });
    }

    /**
     * @throws ValidationException
     */
    public function clear(Outlet $outlet): Outlet
    {
        return DB::transaction(function () use ($outlet): Outlet {
            $this->lock();
            $outlet = $outlet->lockedForUpdate();

            if ($outlet->host_outlet_id === null) {
                throw ValidationException::withMessages(['host_outlet_id' => __('This outlet has no host.')]);
            }

            return $this->save($outlet, 'host_cleared', null);
        });
    }

    protected function save(Outlet $outlet, string $event, ?string $hostId): Outlet
    {
        return $this->audit->as($event, null, function () use ($outlet, $hostId): Outlet {
            $outlet->forceFill(['host_outlet_id' => $hostId])->save();

            return $outlet;
        });
    }

    protected function lock(): void
    {
        DB::select('select pg_advisory_xact_lock(hashtextextended(?, 0))', [self::LOCK_KEY]);
    }

    /**
     * The host and every outlet above it.
     *
     * @return list<string>
     */
    protected function hostChain(Outlet $host): array
    {
        return array_column(DB::select(<<<'SQL'
            with recursive chain(id, host_outlet_id) as (
                select id, host_outlet_id from outlets where id = ?
                union
                select o.id, o.host_outlet_id from outlets o join chain c on o.id = c.host_outlet_id
            )
            select id from chain
            SQL, [$host->getKey()]), 'id');
    }
}
