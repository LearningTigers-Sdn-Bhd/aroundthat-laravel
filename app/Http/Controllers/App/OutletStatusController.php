<?php

namespace App\Http\Controllers\App;

use App\Actions\Outlets\ChangeOutletStatus;
use App\Actions\Outlets\SubmitOutlet;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The owner sends an outlet to admin review, or archives and restores it.
 */
class OutletStatusController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * @throws ValidationException
     */
    public function submit(Outlet $outlet, SubmitOutlet $submitOutlet): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('submit', $outlet);

        $submitOutlet->handle($outlet);

        return $this->done(__('Outlet submitted for review.'));
    }

    /**
     * @throws ValidationException
     */
    public function archive(Outlet $outlet, ChangeOutletStatus $status): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('archive', $outlet);

        $status->archive($outlet);

        return $this->done(__('Outlet archived.'));
    }

    /**
     * @throws ValidationException
     */
    public function restore(Outlet $outlet, ChangeOutletStatus $status): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('archive', $outlet);

        $status->restore($outlet);

        return $this->done(__('Outlet restored.'));
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
