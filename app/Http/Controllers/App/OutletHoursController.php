<?php

namespace App\Http\Controllers\App;

use App\Actions\Outlets\UpdateOpeningHours;
use App\Data\Forms\OpeningHoursData;
use App\Data\OutletData;
use App\Data\OutletHoursData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The outlet's hours tab: its weekly hours and upcoming dates with different hours.
 */
class OutletHoursController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function edit(Request $request, Outlet $outlet): Response
    {
        $this->workspace->ensureOwns($outlet);
        $this->workspace->authorize(Ability::ManagePublicContent);

        $outlet->load(['business', 'hostOutlet', 'dateExceptions']);
        $today = Carbon::now($outlet->timezone)->toDateString();

        return Inertia::render('app/outlets/hours', [
            'outlet' => OutletData::fromModel($outlet),
            'hours' => OutletHoursData::fromModel($outlet, $today),
            'today' => $today,
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
    public function update(Outlet $outlet, OpeningHoursData $data, UpdateOpeningHours $updateOpeningHours): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('updatePublicProfile', $outlet);

        $updateOpeningHours->handle($outlet, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Opening hours saved.')]);

        return back();
    }
}
