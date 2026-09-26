<?php

namespace App\Http\Controllers\App;

use App\Actions\Offers\SyncOfferOutlets;
use App\Data\Forms\OfferOutletsData;
use App\Data\OfferData;
use App\Data\OutletOptionData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\VoucherOffer;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The owner chooses which of the business's outlets redeem an offer.
 */
class OfferOutletController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * The Outlets tab of an offer: the business's outlets that redeem it, and the sponsored ones an admin added.
     */
    public function index(Request $request, VoucherOffer $offer): Response
    {
        $this->workspace->ensureOwns($offer);
        $this->workspace->authorize(Ability::ManageOffers);

        $offer->load(['business', 'outlets.business']);

        return Inertia::render('app/offers/outlets', [
            'offer' => OfferData::fromModel($offer),
            'outletOptions' => OutletOptionData::collect(
                $offer->business->outlets()->whereNull('archived_at')->orderBy('name')->get(),
            ),
            'can' => [
                'update' => $request->user()->can('update', $offer),
            ],
        ]);
    }

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
