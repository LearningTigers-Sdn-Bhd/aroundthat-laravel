<?php

namespace App\Actions\Outlets;

use App\Data\Forms\OutletDetailsData;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save outlet details. They go live at once; the change log keeps the old values.
 */
class UpdateOutlet
{
    /**
     * @throws ValidationException
     */
    public function handle(Outlet $outlet, OutletDetailsData $data): Outlet
    {
        return DB::transaction(function () use ($outlet, $data): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if (! $outlet->isWritable()) {
                throw ValidationException::withMessages([
                    'outlet' => __('This outlet cannot be changed while it is archived, suspended or waiting for review.'),
                ]);
            }

            $outlet->update($data->toModelAttributes());

            return $outlet;
        });
    }
}
