<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Integrations\ChangeIntegrationStatus;
use App\Data\Forms\ReasonData;
use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * An admin suspends or reactivates an integration and, with it, all its API keys.
 */
class IntegrationStatusController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function suspend(Request $request, Integration $integration, ReasonData $data, ChangeIntegrationStatus $status): RedirectResponse
    {
        $status->suspend($request->user(), $integration, $data->reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Integration suspended.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Integration $integration, ChangeIntegrationStatus $status): RedirectResponse
    {
        $status->reactivate($integration);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Integration reactivated.')]);

        return back();
    }
}
