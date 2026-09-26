<?php

namespace App\Enums;

/**
 * Where a staff invitation stands. Worked out from its timestamps, never stored.
 */
enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
