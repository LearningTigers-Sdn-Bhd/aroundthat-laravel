<?php

namespace App\Support\Vouchers;

use RuntimeException;

/**
 * Why the counter cannot redeem a voucher, with a stable code the page and tests can match on, such as `voucher_used`.
 */
class RedemptionRefused extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function because(string $reason): self
    {
        return new self($reason, match ($reason) {
            'invalid_code' => __('That is not a voucher code. Codes have 10 letters and digits.'),
            'voucher_not_found' => __('No voucher has this code.'),
            'voucher_void' => __('This voucher was cancelled.'),
            'voucher_used' => __('This voucher has no uses left.'),
            'offer_inactive' => __('This offer is not running right now.'),
            'offer_not_started' => __('This offer has not started yet.'),
            'offer_expired' => __('This voucher has expired.'),
            'owner_suspended' => __('The business behind this offer cannot trade right now.'),
            'outlet_not_permitted' => __('This voucher cannot be used at this outlet.'),
            'below_min_spend' => __('The bill is below the minimum spend for this offer.'),
            'free_item_value_required' => __('Enter the value of the free item.'),
            'free_item_value_exceeds_bill' => __('The free item cannot be worth more than the bill.'),
            default => __('This voucher cannot be used.'),
        });
    }
}
