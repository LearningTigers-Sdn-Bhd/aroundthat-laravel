<?php

namespace App\Enums;

/**
 * Where a business or outlet is in the one-time admin approval.
 */
enum OnboardingStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Whether the owner may (re)submit it for review.
     */
    public function canBeSubmitted(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }
}
