<?php

namespace App\Actions\Offers;

use App\Jobs\NotifyOfferModeration;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Take an offer down or restore it (admin). A hidden offer cannot be claimed or redeemed, whatever its status,
 * and its owner cannot run it again until an admin restores it. The members who manage offers are emailed.
 */
class ChangeOfferVisibility
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function hide(VoucherOffer $offer, string $reason): VoucherOffer
    {
        return DB::transaction(function () use ($offer, $reason): VoucherOffer {
            $offer = $offer->lockedForUpdate();

            if ($offer->isHidden()) {
                throw ValidationException::withMessages(['offer' => __('This offer is already hidden.')]);
            }

            $this->audit->as('hidden', $reason, fn (): bool => $offer->forceFill(['hidden_at' => now(), 'hidden_reason' => $reason])->save());

            NotifyOfferModeration::dispatch($offer, 'hidden', $reason)->afterCommit();

            return $offer;
        });
    }

    /**
     * @throws ValidationException
     */
    public function unhide(VoucherOffer $offer): VoucherOffer
    {
        return DB::transaction(function () use ($offer): VoucherOffer {
            $offer = $offer->lockedForUpdate();

            if (! $offer->isHidden()) {
                throw ValidationException::withMessages(['offer' => __('This offer is not hidden.')]);
            }

            $this->audit->as('unhidden', null, fn (): bool => $offer->forceFill(['hidden_at' => null, 'hidden_reason' => null])->save());

            NotifyOfferModeration::dispatch($offer, 'unhidden')->afterCommit();

            return $offer;
        });
    }
}
