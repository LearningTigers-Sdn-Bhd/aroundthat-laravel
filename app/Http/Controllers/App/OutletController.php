<?php

namespace App\Http\Controllers\App;

use App\Actions\Outlets\CreateOutlet;
use App\Actions\Outlets\UpdateOutlet;
use App\Data\Forms\OutletDetailsData;
use App\Data\LocationOptionsData;
use App\Data\OutletData;
use App\Data\PlaceProfileData;
use App\Data\RevertNoticeData;
use App\Enums\Ability;
use App\Enums\OnboardingStatus;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Support\QueryFilters\SearchFilter;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The owner's outlets: list, add and edit them. New outlets start as drafts until an admin approves them.
 */
class OutletController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function index(Request $request): Response
    {
        $this->workspace->authorize(Ability::ManageOutlets);

        $business = $this->workspace->business();

        $outlets = QueryBuilder::for($business->outlets(), $request)
            ->allowedFilters(
                SearchFilter::on(['name', 'city']),
                AllowedFilter::exact('onboarding_status'),
            )
            ->allowedSorts('name', 'city')
            ->defaultSort('name')
            ->with('hostOutlet')
            ->paginate(25)
            ->withQueryString();

        $outlets->getCollection()->each->setRelation('business', $business);

        return Inertia::render('app/outlets/index', [
            'outlets' => OutletData::collect($outlets, PaginatedDataCollection::class),
            'onboardingStatuses' => OnboardingStatus::cases(),
            'canCreate' => $request->user()->can('create', [Outlet::class, $business]),
        ]);
    }

    public function create(): Modal
    {
        Gate::authorize('create', [Outlet::class, $this->workspace->business()]);

        return Inertia::modal('app/outlets/create', [
            'timezone' => $this->workspace->business()->timezone,
            'locationOptions' => LocationOptionsData::current(),
        ])->baseRoute('outlets.index');
    }

    /**
     * @throws ValidationException
     */
    public function store(OutletDetailsData $data, CreateOutlet $createOutlet): RedirectResponse
    {
        Gate::authorize('create', [Outlet::class, $this->workspace->business()]);

        $outlet = $createOutlet->handle($this->workspace->business(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlet created. Submit it for review when the details are complete.')]);

        return to_route('outlets.edit', $outlet);
    }

    public function edit(Request $request, Outlet $outlet): Response
    {
        $this->workspace->ensureOwns($outlet);
        $this->workspace->authorize(Ability::ManageOutlets);

        $outlet->load(['business', 'hostOutlet']);

        $managesPublicContent = $this->workspace->membership()->can(Ability::ManagePublicContent);

        return Inertia::render('app/outlets/edit', [
            'outlet' => OutletData::fromModel($outlet),
            'recentReverts' => RevertNoticeData::recentFor($outlet),
            'locationOptions' => LocationOptionsData::current(),
            'place' => $managesPublicContent ? PlaceProfileData::fromModel($outlet->load(['category', 'tags'])) : null,
            'can' => [
                'update' => $request->user()->can('update', $outlet),
                'updateListing' => $request->user()->can('updatePublicProfile', $outlet),
                'submit' => $request->user()->can('submit', $outlet),
                'archive' => $request->user()->can('archive', $outlet),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(Outlet $outlet, OutletDetailsData $data, UpdateOutlet $updateOutlet): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('update', $outlet);

        $updateOutlet->handle($outlet, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Outlet details saved.')]);

        return back();
    }
}
