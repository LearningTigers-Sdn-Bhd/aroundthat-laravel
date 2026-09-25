<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Offers\ManageSponsoredOutlets;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin adds or removes another business's outlet on an offer.
 */
class OfferOutletController extends Controller
{
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
