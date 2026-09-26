<?php

namespace App\Enums;

/**
 * What an integration may do with the partner API. An endpoint that needs a capability the integration lacks returns 403.
 */
enum IntegrationCapability: string
{
    case PlacesRead = 'places:read';
    case EngagementWrite = 'engagement:write';
    case VouchersRead = 'vouchers:read';
    case VouchersClaim = 'vouchers:claim';

    public function label(): string
    {
        return match ($this) {
            self::PlacesRead => __('Read public places'),
            self::EngagementWrite => __('Send engagement events'),
            self::VouchersRead => __('Read voucher offers'),
            self::VouchersClaim => __('Claim, read and void vouchers for guests'),
        };
    }
}
