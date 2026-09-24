<?php

namespace App\Http\Controllers\App;

use App\Data\OutletData;
use App\Data\PlacePreviewData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The outlet's preview tab: its public page as a visitor would see it.
 */
class OutletPreviewController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function __invoke(Request $request, Outlet $outlet): Response
    {
        $this->workspace->ensureOwns($outlet);
        $this->workspace->authorize(Ability::ManagePublicContent);

        $outlet->load(['business', 'hostOutlet']);

        return Inertia::render('app/outlets/preview', [
            'outlet' => OutletData::fromModel($outlet),
            'preview' => PlacePreviewData::fromModel($outlet, now()),
            'can' => [
                'archive' => $request->user()->can('archive', $outlet),
                'submit' => $request->user()->can('submit', $outlet),
            ],
        ]);
    }
}
