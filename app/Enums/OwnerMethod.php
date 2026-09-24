<?php

namespace App\Enums;

/**
 * How an admin gives a newly onboarded business its first owner.
 */
enum OwnerMethod: string
{
    case Existing = 'existing';
    case TemporaryPassword = 'temporary_password';
    case Invite = 'invite';
}
