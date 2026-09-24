<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Outlets\CreateOutlet;
use App\Data\Admin\ActivityData;
use App\Data\Admin\BusinessData;
use App\Data\Admin\HostOutletOptionData;
use App\Data\Admin\OutletData;
use App\Data\Forms\OutletDetailsData;
use App\Data\LocationOptionsData;
use App\Data\PlacePreviewData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;

class OutletController extends Controller
{
    public function create(Business $business): Modal
    {
        $business->load(['approvedBy', 'suspendedBy']);

        return Inertia::modal('admin/outlets/create', [
            'business' => BusinessData::fromModel($business),
            'locationOptions' => LocationOptionsData::current(),
        ])->baseRoute('admin.businesses.show', $business);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, Business $business, OutletDetailsData $data, CreateOutlet $createOutlet): RedirectResponse
    {
        $outlet = $createOutlet->handle($business, $data, $request->boolean('approve_immediately') ? $request->user() : null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlet created.')]);

        return to_route('admin.outlets.show', $outlet);
    }

    /**
     * One outlet: its details, the outlet it sits inside, the outlets inside it, and its change history.
     */
    public function show(Outlet $outlet): Response
    {
        $outlet->load(['business', 'hostOutlet', 'approvedBy', 'suspendedBy']);

        return Inertia::render('admin/outlets/show', [
            'outlet' => OutletData::fromModel($outlet),
            'preview' => PlacePreviewData::fromModel($outlet, now()),
            'hostCandidates' => HostOutletOptionData::collect(
                Outlet::approved()
                    ->whereNull('archived_at')
                    ->whereKeyNot($outlet->getKey())
                    ->with('business')
                    ->orderBy('name')
                    ->get(),
            ),
            'hostedOutlets' => OutletData::collect(
                $outlet->hostedOutlets()->with(['business', 'hostOutlet', 'approvedBy', 'suspendedBy'])->orderBy('name')->get(),
            ),
            'activities' => Inertia::defer(fn () => ActivityData::collect(
                Activity::forSubject($outlet)->with('causer')->latest('id')->limit(100)->get(),
            )),
        ]);
    }
}
