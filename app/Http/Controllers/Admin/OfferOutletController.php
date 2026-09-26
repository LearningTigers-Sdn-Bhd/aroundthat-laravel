<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Offers\ManageSponsoredOutlets;
use App\Data\Admin\HostOutletOptionData;
use App\Data\Admin\OfferData;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An admin adds or removes another business's outlet on an offer.
 */
class OfferOutletController extends Controller
{
    /**
     * The Outlets tab of an offer: where its vouchers can be used, and the outlets an admin can sponsor.
     */
    public function index(VoucherOffer $offer): Response
    {
        $offer->load(['business', 'outlets.business']);

        return Inertia::render('admin/offers/outlets', [
            'offer' => OfferData::fromModel($offer),
            'sponsorCandidates' => HostOutletOptionData::collect(
                Outlet::operational()
                    ->where('business_id', '<>', $offer->business_id)
                    ->whereNotIn('id', $offer->outlets->modelKeys())
                    ->with('business')
                    ->orderBy('name')
                    ->get(),
            ),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, VoucherOffer $offer, ManageSponsoredOutlets $sponsoredOutlets): RedirectResponse
    {
        $validated = $request->validate([
            'outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
        ]);

        $sponsoredOutlets->add($offer, Outlet::whereKey($validated['outlet_id'])->firstOrFail());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sponsored outlet added.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function destroy(VoucherOffer $offer, Outlet $outlet, ManageSponsoredOutlets $sponsoredOutlets): RedirectResponse
    {
        $sponsoredOutlets->remove($offer, $outlet);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sponsored outlet removed.')]);

        return back();
    }
}
