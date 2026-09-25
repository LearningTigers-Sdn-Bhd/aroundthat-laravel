<?php

namespace App\Actions\Vouchers;

use App\Enums\VoucherStatus;
use App\Models\Redemption;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Undo a redemption the counter made by mistake, within Redemption::CANCEL_WINDOW_HOURS. The row stays, marked
 * cancelled, and the voucher gets the use back.
 */
class CancelRedemption
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Redemption $redemption, User $cashier, string $reason): Redemption
    {
        return DB::transaction(function () use ($redemption, $cashier, $reason): Redemption {
            $redemption = $redemption->lockedForUpdate();

            if (! $redemption->isCancellable()) {
                throw ValidationException::withMessages([
                    'redemption' => $redemption->isCancelled()
                        ? __('This redemption is already cancelled.')
                        : trans_choice('Redemptions can only be cancelled within :count hour.|Redemptions can only be cancelled within :count hours.', Redemption::CANCEL_WINDOW_HOURS),
                ]);
            }

            $redemption->forceFill([
                'cancelled_at' => now(),
                'cancelled_by_id' => $cashier->getKey(),
                'cancel_reason' => $reason,
            ])->save();

            $voucher = $redemption->voucher()->lockForUpdate()->firstOrFail();

            $this->audit->as('redemption_cancelled', $reason, fn (): bool => $voucher->forceFill([
                'redemption_count' => max(0, $voucher->redemption_count - 1),
                'status' => $voucher->status === VoucherStatus::Used ? VoucherStatus::Active : $voucher->status,
            ])->save(), ['redemption_id' => $redemption->getKey()]);

            return $redemption;
        });
    }
}
