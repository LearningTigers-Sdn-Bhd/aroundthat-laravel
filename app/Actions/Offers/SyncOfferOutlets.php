<?php

namespace App\Actions\Offers;

use App\Models\Outlet;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Validation\ValidationException;

/**
 * Set which of the business's own outlets redeem an offer, and log the change as outlet names, old and new.
 * Sponsored outlets (of other businesses) stay as they are; only an admin changes them.
 */
class SyncOfferOutlets
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @param  list<string>  $outletIds
     *
     * @throws ValidationException
     */
    public function handle(VoucherOffer $offer, array $outletIds, bool $log = true): void
    {
        $ownOutletIds = Outlet::query()
            ->where('business_id', $offer->business_id)
            ->whereNull('archived_at')
            ->whereKey($outletIds)
            ->pluck('id')
            ->all();

        if (count($ownOutletIds) !== count(array_unique($outletIds))) {
            throw ValidationException::withMessages([
                'outlet_ids' => __('Choose outlets of this business that are not archived.'),
            ]);
        }

        $previousNames = $this->outletNames($offer);

        $sponsoredIds = $offer->outlets()->where('outlets.business_id', '<>', $offer->business_id)->pluck('outlets.id')->all();
        $offer->outlets()->sync([...$ownOutletIds, ...$sponsoredIds]);

        $names = $this->outletNames($offer);

        if ($log && $previousNames !== $names) {
            $offer->touch();

            $this->audit->record($offer, 'outlets_changed', null, [
                'outlets' => ['old' => $previousNames, 'new' => $names],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function outletNames(VoucherOffer $offer): array
    {
        return array_values($offer->outlets()->orderBy('name')->pluck('name')->all());
    }
}
