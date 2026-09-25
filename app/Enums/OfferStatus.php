<?php

namespace App\Enums;

/**
 * The owner's decision about a voucher offer. An offer past its end date has ended whatever this says.
 */
enum OfferStatus: string
{
    /** Being prepared. Nobody can claim or redeem it. */
    case Draft = 'draft';

    /** Running: guests can claim it and cashiers can redeem it inside its dates. */
    case Active = 'active';

    /** Stopped for now. Issued vouchers cannot be redeemed until it is active again. */
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Active'),
            self::Paused => __('Paused'),
        };
    }
}
