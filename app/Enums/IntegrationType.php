<?php

namespace App\Enums;

/**
 * What kind of partner an integration is.
 */
enum IntegrationType: string
{
    case Pms = 'pms';
    case TravelAgency = 'travel_agency';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Pms => __('PMS'),
            self::TravelAgency => __('Travel agency'),
            self::Internal => __('Internal'),
        };
    }
}
