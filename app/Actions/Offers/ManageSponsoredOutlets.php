<?php

namespace App\Actions\Offers;

use App\Models\Outlet;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin lets another business's outlet redeem an offer, or stops it. Adding the outlet is the approval:
 * its cashiers can redeem the offer's vouchers at once. Both are logged on the offer.
 */
class ManageSponsoredOutlets
{
    public function __construct(protected AuditTrail $audit, protected SyncOfferOutlets $syncOutlets) {}

    /**
     * @throws ValidationException
     */
    public function add(VoucherOffer $offer, Outlet $outlet): void
    {
        DB::transaction(function () use ($offer, $outlet): void {
            $offer = $offer->lockedForUpdate();

            $problem = match (true) {
                ! $offer->isSponsoredOutlet($outlet) => __('This outlet belongs to the offer\'s business. Its owner chooses it on the offer.'),
                ! $outlet->isOperational() => __('Only an approved outlet that is trading can redeem a sponsored offer.'),
                $offer->outlets()->whereKey($outlet->getKey())->exists() => __('This outlet already redeems the offer.'),
                default => null,
            };

            if ($problem !== null) {
                throw ValidationException::withMessages(['outlet_id' => $problem]);
            }

            $this->change($offer, fn () => $offer->outlets()->attach($outlet->getKey()), 'sponsored_outlet_added', $outlet);
        });
    }

    /**
     * @throws ValidationException
     */
    public function remove(VoucherOffer $offer, Outlet $outlet): void
    {
        DB::transaction(function () use ($offer, $outlet): void {
            $offer = $offer->lockedForUpdate();

            if (! $offer->isSponsoredOutlet($outlet) || ! $offer->outlets()->whereKey($outlet->getKey())->exists()) {
                throw ValidationException::withMessages(['outlet_id' => __('This outlet is not a sponsored outlet of the offer.')]);
            }

            $this->change($offer, fn () => $offer->outlets()->detach($outlet->getKey()), 'sponsored_outlet_removed', $outlet);
        });
    }

    protected function change(VoucherOffer $offer, callable $change, string $event, Outlet $outlet): void
    {
        $previousNames = $this->syncOutlets->outletNames($offer);

        $change();
        $offer->touch();

        $this->audit->record($offer, $event, null, [
            'outlet' => $outlet->name,
            'outlets' => ['old' => $previousNames, 'new' => $this->syncOutlets->outletNames($offer)],
        ]);
    }
}
