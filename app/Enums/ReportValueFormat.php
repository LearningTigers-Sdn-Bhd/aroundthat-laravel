<?php

namespace App\Enums;

/**
 * How the page and the CSV show a value in a report column or tile.
 */
enum ReportValueFormat: string
{
    case Text = 'text';
    case Count = 'count';

    /** A decimal string with two places, in the voucher currency. */
    case Money = 'money';

    /** A share from 0 to 100, or null when there is nothing to divide by. */
    case Percent = 'percent';
}
