<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Businesses\ReviewBusiness;
use App\Actions\Businesses\SuspendBusiness;
use App\Data\Forms\ReasonData;
use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin's review and suspension decisions on a business.
 */
class BusinessStatusController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function approve(Request $request, Business $business, ReviewBusiness $review): RedirectResponse
    {
        $review->approve($request->user(), $business);

        return $this->done(__('Business approved.'));
    }

    /**
     * @throws ValidationException
     */
    public function reject(Business $business, ReasonData $data, ReviewBusiness $review): RedirectResponse
    {
        $review->reject($business, $data->reason);

        return $this->done(__('Business rejected.'));
    }

    /**
     * @throws ValidationException
     */
    public function suspend(Request $request, Business $business, ReasonData $data, SuspendBusiness $suspension): RedirectResponse
    {
        $suspension->suspend($request->user(), $business, $data->reason);

        return $this->done(__('Business suspended.'));
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Business $business, SuspendBusiness $suspension): RedirectResponse
    {
        $suspension->reactivate($business);

        return $this->done(__('Business reactivated.'));
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
