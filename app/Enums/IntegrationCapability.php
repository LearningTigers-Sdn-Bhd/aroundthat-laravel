<?php

namespace App\Enums;

/**
 * What an integration may do with the partner API. An endpoint that needs a capability the integration lacks returns 403.
 */
enum IntegrationCapability: string
{
    case PlacesRead = 'places:read';
    case EngagementWrite = 'engagement:write';

    public function label(): string
    {
        return match ($this) {
            self::PlacesRead => __('Read public places'),
            self::EngagementWrite => __('Send engagement events'),
        };
    }
}
