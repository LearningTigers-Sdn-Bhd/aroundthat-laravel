<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Outlets\ChangeOutletStatus;
use App\Actions\Outlets\ReviewOutlet;
use App\Data\Forms\ReasonData;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin's review, suspension and archiving decisions on an outlet.
 */
class OutletStatusController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function approve(Request $request, Outlet $outlet, ReviewOutlet $review): RedirectResponse
    {
        $review->approve($request->user(), $outlet);

        return $this->done(__('Outlet approved.'));
    }

    /**
     * @throws ValidationException
     */
    public function reject(Outlet $outlet, ReasonData $data, ReviewOutlet $review): RedirectResponse
    {
        $review->reject($outlet, $data->reason);

        return $this->done(__('Outlet rejected.'));
    }

    /**
     * @throws ValidationException
     */
    public function suspend(Request $request, Outlet $outlet, ReasonData $data, ChangeOutletStatus $status): RedirectResponse
    {
        $status->suspend($request->user(), $outlet, $data->reason);

        return $this->done(__('Outlet suspended.'));
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Outlet $outlet, ChangeOutletStatus $status): RedirectResponse
    {
        $status->reactivate($outlet);

        return $this->done(__('Outlet reactivated.'));
    }

    /**
     * @throws ValidationException
     */
    public function archive(Outlet $outlet, ChangeOutletStatus $status): RedirectResponse
    {
        $status->archive($outlet);

        return $this->done(__('Outlet archived.'));
    }

    /**
     * @throws ValidationException
     */
    public function restore(Outlet $outlet, ChangeOutletStatus $status): RedirectResponse
    {
        $status->restore($outlet);

        return $this->done(__('Outlet restored.'));
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
