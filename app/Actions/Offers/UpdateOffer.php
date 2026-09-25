<?php

namespace App\Actions\Offers;

use App\Data\Forms\OfferFormData;
use App\Data\Forms\OfferLimitsData;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save an offer's terms. Once vouchers are issued, guests hold those terms, so only the fields in
 * VoucherOffer::EDITABLE_AFTER_ISSUE may change, the end date cannot move into the past, and the limit
 * cannot drop below the vouchers already issued.
 */
class UpdateOffer
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(VoucherOffer $offer, OfferFormData|OfferLimitsData $data): VoucherOffer
    {
        return DB::transaction(function () use ($offer, $data): VoucherOffer {
            $offer = $offer->lockedForUpdate();
            $offer->load('business');

            if ($offer->business->isSuspended()) {
                throw ValidationException::withMessages([
                    'offer' => __('Offers cannot be changed while the business is suspended.'),
                ]);
            }

            $offer->fill($data->toModelAttributes($offer->business->timezone));

            if ($offer->isLocked()) {
                $this->ensureOnlyEditableFieldsChanged($offer);
            }

            if ($offer->voucher_limit !== null && $offer->voucher_limit < $offer->issued_count) {
                throw ValidationException::withMessages([
                    'voucher_limit' => trans_choice('The limit cannot be lower than the :count voucher already issued.|The limit cannot be lower than the :count vouchers already issued.', $offer->issued_count),
                ]);
            }

            $this->audit->as('details_changed', null, fn (): bool => $offer->save());

            return $offer;
        });
    }

    /**
     * @throws ValidationException
     */
    protected function ensureOnlyEditableFieldsChanged(VoucherOffer $offer): void
    {
        $locked = array_diff(array_keys($offer->getDirty()), VoucherOffer::EDITABLE_AFTER_ISSUE);

        if ($locked !== []) {
            throw ValidationException::withMessages([
                'offer' => __('The terms cannot change after vouchers are issued. You can still change the description, the end date and the limit.'),
            ]);
        }

        if ($offer->isDirty('ends_at') && $offer->ends_at->isPast()) {
            throw ValidationException::withMessages([
                'ends_at' => __('Vouchers are already issued, so the end date cannot be in the past. Use now to end the offer.'),
            ]);
        }
    }
}
