<?php

namespace App\Actions\Vouchers;

use App\Enums\VoucherStatus;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use App\Support\Vouchers\DiscountCalculator;
use App\Support\Vouchers\RedemptionRefused;
use App\Support\Vouchers\VoucherCode;
use App\Support\Vouchers\VoucherEligibility;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Use a voucher once at an outlet. The counter sends a fresh idempotency key with each redemption, so a
 * double-tap or a retry after a lost response returns the first redemption instead of using the voucher twice.
 * A refusal is logged on the outlet.
 */
class RedeemVoucher
{
    public function __construct(
        protected AuditTrail $audit,
        protected VoucherEligibility $eligibility,
        protected DiscountCalculator $calculator,
    ) {}

    /**
     * @throws RedemptionRefused
     */
    public function handle(User $cashier, Outlet $outlet, string $code, string $billAmount, ?string $freeItemValue, string $idempotencyKey): Redemption
    {
        $existing = $this->existing($outlet, $idempotencyKey);

        if ($existing !== null) {
            return $existing;
        }

        try {
            return DB::transaction(fn (): Redemption => $this->redeem($cashier, $outlet, $code, $billAmount, $freeItemValue, $idempotencyKey));
        } catch (UniqueConstraintViolationException) {
            return $this->existing($outlet, $idempotencyKey) ?? throw RedemptionRefused::because('voucher_used');
        } catch (RedemptionRefused $refusal) {
            $this->audit->record($outlet, 'redemption_refused', $refusal->getMessage(), [
                'reason' => $refusal->reason,
                'code_prefix' => substr(VoucherCode::normalize($code), 0, 4),
            ]);

            throw $refusal;
        }
    }

    protected function redeem(User $cashier, Outlet $outlet, string $code, string $billAmount, ?string $freeItemValue, string $idempotencyKey): Redemption
    {
        $voucher = $this->eligibility->check($code, $outlet, lock: true);
        $discount = $this->calculator->calculate($voucher->offer, $billAmount, $freeItemValue);

        $redemption = $voucher->redemptions()->forceCreate([
            'outlet_id' => $outlet->getKey(),
            'user_id' => $cashier->getKey(),
            'bill_amount' => $discount->billAmount,
            'discount_amount' => $discount->discountAmount,
            'capped' => $discount->capped,
            'idempotency_key' => $idempotencyKey,
            'redeemed_at' => now(),
        ]);

        $count = $voucher->redemption_count + 1;

        $this->audit->as('redeemed', null, fn (): bool => $voucher->forceFill([
            'redemption_count' => $count,
            'status' => $count >= $voucher->offer->uses_per_voucher ? VoucherStatus::Used : VoucherStatus::Active,
        ])->save(), ['outlet' => $outlet->name, 'redemption_id' => $redemption->getKey()]);

        return $redemption;
    }

    protected function existing(Outlet $outlet, string $idempotencyKey): ?Redemption
    {
        return Redemption::query()
            ->where('outlet_id', $outlet->getKey())
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }
}
