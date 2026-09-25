<?php

namespace App\Http\Controllers\App;

use App\Actions\Offers\SyncOfferOutlets;
use App\Data\Forms\OfferOutletsData;
use App\Http\Controllers\Controller;
use App\Models\VoucherOffer;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The owner chooses which of the business's outlets redeem an offer.
 */
class OfferOutletController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * @throws ValidationException
     */
    public function update(VoucherOffer $offer, OfferOutletsData $data, SyncOfferOutlets $syncOutlets): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('update', $offer);

        DB::transaction(fn () => $syncOutlets->handle($offer->lockedForUpdate(), $data->outletIds));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlets saved.')]);

        return back();
    }
}
