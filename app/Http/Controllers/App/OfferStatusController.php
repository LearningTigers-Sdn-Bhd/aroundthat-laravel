<?php

namespace App\Http\Controllers\App;

use App\Actions\Offers\ChangeOfferStatus;
use App\Http\Controllers\Controller;
use App\Models\VoucherOffer;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The owner runs or pauses an offer.
 */
class OfferStatusController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * @throws ValidationException
     */
    public function activate(VoucherOffer $offer, ChangeOfferStatus $status): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('update', $offer);

        $status->activate($offer);

        return $this->done(__('Offer activated.'));
    }

    /**
     * @throws ValidationException
     */
    public function pause(VoucherOffer $offer, ChangeOfferStatus $status): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('update', $offer);

        $status->pause($offer);

        return $this->done(__('Offer paused.'));
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
