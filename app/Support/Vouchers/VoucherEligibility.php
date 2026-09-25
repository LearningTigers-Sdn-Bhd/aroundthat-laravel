<?php

namespace App\Support\Vouchers;

use App\Enums\VoucherStatus;
use App\Models\Outlet;
use App\Models\Voucher;

/**
 * Find the voucher behind a typed or scanned code and check that it can be used at an outlet now.
 * The checks run in a fixed order, so the cashier sees the first problem.
 */
class VoucherEligibility
{
    /**
     * @param  bool  $lock  Lock the voucher row, when the caller is about to use it.
     *
     * @throws RedemptionRefused
     */
    public function check(string $input, Outlet $outlet, bool $lock = false): Voucher
    {
        $code = VoucherCode::normalize($input);

        if (! VoucherCode::isWellFormed($code)) {
            throw RedemptionRefused::because('invalid_code');
        }

        $voucher = Voucher::query()
            ->where('code_hash', VoucherCode::hash($code))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();

        if ($voucher === null) {
            throw RedemptionRefused::because('voucher_not_found');
        }

        $voucher->load('offer.business');
        $offer = $voucher->offer;

        $reason = match (true) {
            $voucher->status === VoucherStatus::Void => 'voucher_void',
            $voucher->status === VoucherStatus::Used || $voucher->usesLeft() === 0 => 'voucher_used',
            ! $offer->isOfferable() => 'offer_inactive',
            ! $offer->hasStarted() => 'offer_not_started',
            $offer->hasEnded() || $voucher->isExpired() => 'offer_expired',
            ! $offer->business->isApproved() || $offer->business->isSuspended() => 'owner_suspended',
            ! $outlet->isOperational() || ! $offer->outlets()->whereKey($outlet->getKey())->exists() => 'outlet_not_permitted',
            default => null,
        };

        if ($reason !== null) {
            throw RedemptionRefused::because($reason);
        }

        return $voucher;
    }
}
