<?php

namespace App\Actions\Offers;

use App\Data\Forms\OfferFormData;
use App\Data\Forms\OfferOutletsData;
use App\Models\Business;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Add a voucher offer to a business as a draft, redeemable at the chosen outlets of that business.
 */
class CreateOffer
{
    public function __construct(protected AuditTrail $audit, protected SyncOfferOutlets $syncOutlets) {}

    /**
     * @throws ValidationException
     */
    public function handle(Business $business, OfferFormData $data, OfferOutletsData $outlets): VoucherOffer
    {
        return DB::transaction(function () use ($business, $data, $outlets): VoucherOffer {
            $business = $business->lockedForUpdate();

            if ($business->isSuspended()) {
                throw ValidationException::withMessages([
                    'business' => __('Offers cannot be added to a suspended business.'),
                ]);
            }

            return $this->audit->as('created', null, function () use ($business, $data, $outlets): VoucherOffer {
                $offer = $business->voucherOffers()->create($data->toModelAttributes($business->timezone));

                $this->syncOutlets->handle($offer, $outlets->outletIds, log: false);

                return $offer;
            });
        });
    }
}
