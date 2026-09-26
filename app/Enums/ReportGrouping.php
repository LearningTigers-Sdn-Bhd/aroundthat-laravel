<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use LogicException;

/**
 * What one row of a report stands for: a stretch of time, an outlet or an offer.
 */
enum ReportGrouping: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Outlet = 'outlet';
    case Offer = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::Day => __('Day'),
            self::Week => __('Week'),
            self::Month => __('Month'),
            self::Outlet => __('Outlet'),
            self::Offer => __('Offer'),
        };
    }

    /**
     * Whether each row is a stretch of time, so the report lists every one of them, empty or not.
     */
    public function isTime(): bool
    {
        return in_array($this, [self::Day, self::Week, self::Month], true);
    }

    /**
     * How a row of this time grouping is named, from the first day it covers.
     */
    public function bucketLabel(CarbonInterface $start): string
    {
        return match ($this) {
            self::Day => $start->format('D, j M'),
            self::Week => __('Week of :date', ['date' => $start->format('j M')]),
            self::Month => $start->format('M Y'),
            default => throw new LogicException("{$this->value} is not a time grouping."),
        };
    }
}
