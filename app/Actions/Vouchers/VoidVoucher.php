<?php

namespace App\Actions\Vouchers;

use App\Enums\VoidReason;
use App\Enums\VoucherStatus;
use App\Models\Voucher;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cancel a voucher for good, so it can never be redeemed. Staff give a reason in words; the partner that claimed
 * a voucher gives one of the VoidReason codes.
 */
class VoidVoucher
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Voucher $voucher, string|VoidReason $reason): Voucher
    {
        $reason = $reason instanceof VoidReason ? $reason->value : $reason;

        return DB::transaction(function () use ($voucher, $reason): Voucher {
            $voucher = $voucher->lockedForUpdate();

            if ($voucher->effectiveStatus() !== VoucherStatus::Active->value) {
                throw ValidationException::withMessages([
                    'voucher' => __('Only an active voucher that has not expired can be voided.'),
                ]);
            }

            $this->audit->as('voided', $reason, fn (): bool => $voucher->forceFill([
                'status' => VoucherStatus::Void,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save());

            return $voucher;
        });
    }
}
