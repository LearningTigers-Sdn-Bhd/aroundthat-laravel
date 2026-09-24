<?php

namespace App\Actions\Businesses;

use App\Data\Forms\BusinessPublicProfileData;
use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save what visitors see about a business. It goes live at once; the change log keeps the old values.
 */
class UpdateBusinessPublicProfile
{
    /**
     * @throws ValidationException
     */
    public function handle(Business $business, BusinessPublicProfileData $data): Business
    {
        return DB::transaction(function () use ($business, $data): Business {
            $business = $business->lockedForUpdate();

            if (! $business->isWritable()) {
                throw ValidationException::withMessages([
                    'business' => __('This business cannot be changed while it is suspended or waiting for review.'),
                ]);
            }

            $business->update([
                'summary' => $data->summary,
                'description' => $data->description,
            ]);

            return $business;
        });
    }
}
