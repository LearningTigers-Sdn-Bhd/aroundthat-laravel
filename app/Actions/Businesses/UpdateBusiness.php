<?php

namespace App\Actions\Businesses;

use App\Data\Forms\BusinessDetailsData;
use App\Models\Business;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save business details. They go live at once; the change log keeps the old values.
 */
class UpdateBusiness
{
    public function __construct(protected AuditTrail $audit) {}

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

            $this->audit->contentChange('details_changed', fn (): bool => $business->update($data->toModelAttributes()));

            return $business;
        });
    }
}
