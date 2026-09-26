<?php

namespace App\Http\Controllers\App;

use App\Actions\Outlets\UpdateOutletPublicProfile;
use App\Data\Forms\OutletListingData;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Whether visitors can find the outlet, set from its details tab.
 */
class OutletListingController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * @throws ValidationException
     */
    public function update(Outlet $outlet, OutletListingData $data, UpdateOutletPublicProfile $updatePublicProfile): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('updatePublicProfile', $outlet);

        $updatePublicProfile->handle($outlet, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => $data->isListed ? __('Outlet listed.') : __('Outlet unlisted.')]);

        return back();
    }
}
