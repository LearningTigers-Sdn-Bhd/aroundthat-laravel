<?php

namespace App\Http\Controllers\App;

use App\Actions\Outlets\UpdateOutletPublicProfile;
use App\Data\Forms\OutletLocationData;
use App\Data\OutletData;
use App\Data\PlaceProfileData;
use App\Data\RevertNoticeData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The outlet's location tab: where it sits on the map.
 */
class OutletLocationController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function edit(Request $request, Outlet $outlet): Response
    {
        $this->workspace->ensureOwns($outlet);
        $this->workspace->authorize(Ability::ManagePublicContent);

        $outlet->load(['business', 'hostOutlet', 'category', 'tags']);

        return Inertia::render('app/outlets/location', [
            'outlet' => OutletData::fromModel($outlet),
            'recentReverts' => RevertNoticeData::recentFor($outlet),
            'place' => PlaceProfileData::fromModel($outlet),
            'can' => [
                'update' => $request->user()->can('updatePublicProfile', $outlet),
                'archive' => $request->user()->can('archive', $outlet),
                'submit' => $request->user()->can('submit', $outlet),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(Outlet $outlet, OutletLocationData $data, UpdateOutletPublicProfile $updatePublicProfile): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('updatePublicProfile', $outlet);

        $updatePublicProfile->handle($outlet, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Location saved.')]);

        return back();
    }
}
