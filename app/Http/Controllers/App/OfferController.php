<?php

namespace App\Http\Controllers\App;

use App\Actions\Offers\CreateOffer;
use App\Actions\Offers\UpdateOffer;
use App\Data\Forms\OfferFormData;
use App\Data\Forms\OfferLimitsData;
use App\Data\Forms\OfferOutletsData;
use App\Data\OfferData;
use App\Data\OutletOptionData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\VoucherOffer;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

/**
 * The business's voucher offers: list, add and edit them. Editing is the Details tab of an offer. New offers start as drafts.
 */
class OfferController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function index(Request $request): Response
    {
        $this->workspace->authorize(Ability::ManageOffers);

        $business = $this->workspace->business();

        return Inertia::render('app/offers/index', [
            'offers' => OfferData::collect(
                $business->voucherOffers()->with('outlets.business')->latest()->get()->each->setRelation('business', $business),
            ),
            'canCreate' => $request->user()->can('create', [VoucherOffer::class, $business]),
        ]);
    }

    public function create(): Modal
    {
        Gate::authorize('create', [VoucherOffer::class, $this->workspace->business()]);

        return Inertia::modal('app/offers/create', [
            'timezone' => $this->workspace->business()->timezone,
            'outletOptions' => OutletOptionData::collect(
                $this->workspace->business()->outlets()->whereNull('archived_at')->orderBy('name')->get(),
            ),
            'currency' => config('vouchers.currency'),
        ])->baseRoute('offers.index');
    }

    /**
     * @throws ValidationException
     */
    public function store(OfferFormData $data, OfferOutletsData $outlets, CreateOffer $createOffer): RedirectResponse
    {
        Gate::authorize('create', [VoucherOffer::class, $this->workspace->business()]);

        $offer = $createOffer->handle($this->workspace->business(), $data, $outlets);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer saved as a draft. Activate it when it is ready.')]);

        return to_route('offers.edit', $offer);
    }

    public function edit(Request $request, VoucherOffer $offer): Response
    {
        $this->workspace->ensureOwns($offer);
        $this->workspace->authorize(Ability::ManageOffers);

        $offer->load(['business', 'outlets.business']);

        return Inertia::render('app/offers/edit', [
            'offer' => OfferData::fromModel($offer),
            'timezone' => $offer->business->timezone,
            'can' => [
                'update' => $request->user()->can('update', $offer),
            ],
        ]);
    }

    /**
     * Save the terms, or only the fields that stay editable once vouchers are issued.
     *
     * @throws ValidationException
     */
    public function update(Request $request, VoucherOffer $offer, UpdateOffer $updateOffer): RedirectResponse
    {
        $this->workspace->ensureOwns($offer);
        Gate::authorize('update', $offer);

        $data = $offer->isLocked()
            ? OfferLimitsData::validateAndCreate($request->all())
            : OfferFormData::validateAndCreate($request->all());

        $updateOffer->handle($offer, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Offer saved.')]);

        return back();
    }
}
