<?php

namespace App\Actions\Businesses;

use App\Data\Forms\BusinessDetailsData;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save business details. They go live at once; the change log keeps the old values.
 */
class UpdateBusiness
{
    /**
     * @throws ValidationException
     */
    public function handle(Business $business, BusinessDetailsData $data): Business
    {
        return DB::transaction(function () use ($business, $data): Business {
            $business = $business->lockedForUpdate();

            if (! $business->isWritable()) {
                throw ValidationException::withMessages([
                    'business' => __('This business cannot be changed while it is suspended or waiting for review.'),
                ]);
            }

            $business->update($data->toModelAttributes());

            return $business;
        });
    }
}
