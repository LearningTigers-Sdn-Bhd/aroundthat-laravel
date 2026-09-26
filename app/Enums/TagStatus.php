<?php

namespace App\Enums;

/**
 * Whether a tag may be shown publicly. Owner-created tags wait for an admin; owners never see this state.
 */
enum TagStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
