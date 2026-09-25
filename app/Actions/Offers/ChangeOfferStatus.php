<?php

namespace App\Actions\Offers;

use App\Enums\OfferStatus;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The owner runs or pauses an offer. Pausing stops claims and redemptions; issued vouchers work again once it is active.
 */
class ChangeOfferStatus
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function activate(VoucherOffer $offer): VoucherOffer
    {
        return DB::transaction(function () use ($offer): VoucherOffer {
            $offer = $offer->lockedForUpdate();
            $offer->load('business');

            $problem = match (true) {
                $offer->status === OfferStatus::Active => __('This offer is already active.'),
                ! $offer->business->isApproved() || $offer->business->isSuspended() => __('Offers can run once an admin approves the business, and not while it is suspended.'),
                $offer->isHidden() => __('An admin hid this offer. It can run again once an admin restores it.'),
                $offer->hasEnded() => __('This offer has ended. Move its end date to run it again.'),
                ! $offer->outlets()->exists() => __('Choose at least one outlet where the vouchers can be used.'),
                default => null,
            };

            if ($problem !== null) {
                throw ValidationException::withMessages(['offer' => $problem]);
            }

            $this->audit->as('activated', null, fn (): bool => $offer->forceFill(['status' => OfferStatus::Active])->save());

            return $offer;
        });
    }

    /**
     * @throws ValidationException
     */
    public function pause(VoucherOffer $offer): VoucherOffer
    {
        return DB::transaction(function () use ($offer): VoucherOffer {
            $offer = $offer->lockedForUpdate();

            if ($offer->status !== OfferStatus::Active) {
                throw ValidationException::withMessages(['offer' => __('Only an active offer can be paused.')]);
            }

            $this->audit->as('paused', null, fn (): bool => $offer->forceFill(['status' => OfferStatus::Paused])->save());

            return $offer;
        });
    }
}
