<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Validation\ValidationException;

/**
 * Checks the outlets a manager or cashier is given: at least one, all approved, active and in the same business.
 */
class AssignableOutlets
{
    /**
     * @param  array<int, string>  $outletIds
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    public function resolve(Business $business, array $outletIds): array
    {
        $outletIds = array_values(array_unique(array_filter($outletIds)));

        $found = $business->outlets()
            ->operational()
            ->whereKey($outletIds)
            ->get()
            ->map(fn (Outlet $outlet): string => $outlet->id)
            ->all();

        if ($found === [] || count($found) !== count($outletIds)) {
            throw ValidationException::withMessages([
                'outlet_ids' => __('Select at least one approved, active outlet of this business.'),
            ]);
        }

        return $found;
    }
}
