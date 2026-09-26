<?php

namespace App\Http\Controllers\App;

use App\Actions\Businesses\SubmitBusiness;
use App\Actions\Businesses\UpdateBusiness;
use App\Data\BusinessData;
use App\Data\BusinessPlaceData;
use App\Data\Forms\BusinessDetailsData;
use App\Data\LocationOptionsData;
use App\Data\RevertNoticeData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The owner's view of their business details, and sending them to admin review.
 */
class BusinessController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function edit(Request $request): Response
    {
        $this->workspace->authorize(Ability::ManageBusiness);

        $business = $this->workspace->business();

        return Inertia::render('app/business/edit', [
            'business' => BusinessData::fromModel($business),
            'place' => BusinessPlaceData::fromModel($business),
            'recentReverts' => RevertNoticeData::recentFor($business),
            'locationOptions' => LocationOptionsData::current(),
            'can' => [
                'update' => $request->user()->can('update', $business),
                'submit' => $request->user()->can('submit', $business),
                'updatePublicProfile' => $request->user()->can('updatePublicProfile', $business),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(BusinessDetailsData $data, UpdateBusiness $updateBusiness): RedirectResponse
    {
        Gate::authorize('update', $this->workspace->business());

        $updateBusiness->handle($this->workspace->business(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business details saved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function submit(SubmitBusiness $submitBusiness): RedirectResponse
    {
        Gate::authorize('submit', $this->workspace->business());

        $submitBusiness->handle($this->workspace->business());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business submitted for review.')]);

        return back();
    }
}
