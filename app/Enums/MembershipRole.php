<?php

namespace App\Enums;

/**
 * A member's role inside one business. The whole permission matrix lives in can().
 */
enum MembershipRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function can(Ability $ability): bool
    {
        return match ($this) {
            self::Owner => true,
            self::Manager => in_array($ability, [
                Ability::Scan,
                Ability::ViewTodayActivity,
                Ability::ViewReports,
                Ability::ViewStatements,
                Ability::ManageOffers,
            ], true),
            self::Cashier => in_array($ability, [
                Ability::Scan,
                Ability::ViewTodayActivity,
            ], true),
        };
    }

    /**
     * Whether the role covers every outlet of the business instead of assigned ones.
     */
    public function coversAllOutlets(): bool
    {
        return $this === self::Owner;
    }
}
