<?php

namespace App\Support\Vouchers;

use App\Enums\DiscountType;
use App\Models\VoucherOffer;
use InvalidArgumentException;
use RoundingMode;

/**
 * Work out an offer's discount on a bill with decimal maths, never floats. A percentage rounds half up to the cent
 * and stops at the offer's cap; an amount never goes past the bill; a free item is worth what the cashier enters.
 */
class DiscountCalculator
{
    /**
     * @param  string  $billAmount  A positive amount with at most two decimals, already validated.
     * @param  string|null  $freeItemValue  The free item's price on the bill, for free item offers.
     *
     * @throws RedemptionRefused
     */
    public function calculate(VoucherOffer $offer, string $billAmount, ?string $freeItemValue = null): Discount
    {
        $bill = $this->decimal($billAmount);

        if ($offer->min_spend_amount !== null && bccomp($bill, $offer->min_spend_amount, 2) < 0) {
            throw RedemptionRefused::because('below_min_spend');
        }

        [$discount, $capped] = match ($offer->discount_type) {
            DiscountType::Percentage => $this->percentage($offer, $bill),
            DiscountType::Amount => $this->atMostBill($this->decimal((string) $offer->discount_value), $bill),
            DiscountType::FreeItem => $this->freeItem($freeItemValue === null ? null : $this->decimal($freeItemValue), $bill),
        };

        return new Discount(
            billAmount: $bill,
            discountAmount: $discount,
            netAmount: bcsub($bill, $discount, 2),
            capped: $capped,
        );
    }

    /**
     * @param  numeric-string  $bill
     * @return array{0: numeric-string, 1: bool}
     */
    protected function percentage(VoucherOffer $offer, string $bill): array
    {
        $discount = bcadd(bcround(bcdiv(bcmul($bill, $this->decimal((string) $offer->discount_value), 6), '100', 6), 2, RoundingMode::HalfAwayFromZero), '0', 2);

        if ($offer->max_discount_amount !== null && bccomp($discount, $offer->max_discount_amount, 2) > 0) {
            return [$offer->max_discount_amount, true];
        }

        return [$discount, false];
    }

    /**
     * @param  numeric-string  $amount
     * @param  numeric-string  $bill
     * @return array{0: numeric-string, 1: bool}
     */
    protected function atMostBill(string $amount, string $bill): array
    {
        return bccomp($amount, $bill, 2) > 0 ? [$bill, true] : [bcadd($amount, '0', 2), false];
    }

    /**
     * @param  numeric-string|null  $value
     * @param  numeric-string  $bill
     * @return array{0: numeric-string, 1: bool}
     *
     * @throws RedemptionRefused
     */
    protected function freeItem(?string $value, string $bill): array
    {
        if ($value === null || bccomp($value, '0', 2) <= 0) {
            throw RedemptionRefused::because('free_item_value_required');
        }

        if (bccomp($value, $bill, 2) > 0) {
            throw RedemptionRefused::because('free_item_value_exceeds_bill');
        }

        return [bcadd($value, '0', 2), false];
    }

    /**
     * An amount as a decimal string with two places.
     *
     * @return numeric-string
     *
     * @throws InvalidArgumentException When the amount is not a number, which validation should have caught.
     */
    protected function decimal(string $amount): string
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException("[{$amount}] is not an amount.");
        }

        return bcadd($amount, '0', 2);
    }
}
