<?php

namespace App\Enums;

/**
 * How a voucher offer lowers the bill.
 */
enum DiscountType: string
{
    /** A share of the bill, optionally capped. */
    case Percentage = 'percentage';

    /** A fixed amount off, never more than the bill. */
    case Amount = 'amount';

    /** One named item for free; the cashier enters its value. */
    case FreeItem = 'free_item';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => __('Percentage off'),
            self::Amount => __('Amount off'),
            self::FreeItem => __('Free item'),
        };
    }
}
