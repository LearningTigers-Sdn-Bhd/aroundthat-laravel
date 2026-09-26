<?php

namespace App\Enums;

/**
 * A ready-made date range for a report, counted in the business's timezone. Custom takes the two dates given.
 */
enum ReportPeriodPreset: string
{
    case Today = 'today';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today => __('Today'),
            self::Last7Days => __('Last 7 days'),
            self::Last30Days => __('Last 30 days'),
            self::ThisMonth => __('This month'),
            self::LastMonth => __('Last month'),
            self::Custom => __('Custom dates'),
        };
    }
}
