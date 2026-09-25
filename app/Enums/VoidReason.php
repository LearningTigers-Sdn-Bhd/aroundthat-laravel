<?php

namespace App\Enums;

/**
 * Why a partner cancelled a voucher it claimed. Staff give a free-text reason instead.
 */
enum VoidReason: string
{
    case GuestCancelled = 'guest_cancelled';
    case DuplicateClaim = 'duplicate_claim';
    case IssuedInError = 'issued_in_error';
    case SuspectedAbuse = 'suspected_abuse';
}
