<?php

namespace App\Enums;

/**
 * A narrowing a report may accept on top of its dates.
 */
enum ReportFilter: string
{
    /** Only one of the business's offers. */
    case Offer = 'offer';

    /** Only one of the outlets the member can see. */
    case Outlet = 'outlet';
}
