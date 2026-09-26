<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Offers\ChangeOfferVisibility;
use App\Data\Forms\ReasonData;
use App\Http\Controllers\Controller;
use App\Models\VoucherOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin takes an offer down or restores it.
 */
class OfferStatusController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function hide(VoucherOffer $offer, ReasonData $data, ChangeOfferVisibility $visibility): RedirectResponse
    {
        $visibility->hide($offer, $data->reason);

        return $this->done(__('Offer hidden. Its vouchers cannot be claimed or used.'));
    }

    /**
     * @throws ValidationException
     */
    public function unhide(VoucherOffer $offer, ChangeOfferVisibility $visibility): RedirectResponse
    {
        $visibility->unhide($offer);

        return $this->done(__('Offer restored.'));
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
