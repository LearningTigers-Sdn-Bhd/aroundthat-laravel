<?php

namespace App\Enums;

/**
 * Where a voucher is in its life. "Expired" is not stored; see Voucher::effectiveStatus().
 */
enum VoucherStatus: string
{
    /** It has uses left. */
    case Active = 'active';

    /** Every use is spent. Cancelling a redemption makes it active again. */
    case Used = 'used';

    /** Staff or the partner that claimed it cancelled it for good. */
    case Void = 'void';
}
