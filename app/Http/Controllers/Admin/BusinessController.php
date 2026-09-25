<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Businesses\OnboardBusiness;
use App\Data\Admin\BusinessData;
use App\Data\Forms\OnboardBusinessData;
use App\Data\LocationOptionsData;
use App\Enums\OnboardingStatus;
use App\Enums\OwnerMethod;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\QueryFilters\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BusinessController extends Controller
{
    public function index(Request $request): Response
    {
        $businesses = QueryBuilder::for(Business::class, $request)
            ->allowedFilters(
                SearchFilter::on(['name', 'registered_name', 'registration_number', 'contact_email']),
                AllowedFilter::exact('onboarding_status'),
                AllowedFilter::callback('suspended', fn (Builder $query, mixed $value) => $value === true
                    ? $query->whereNotNull('suspended_at')
                    : $query->whereNull('suspended_at')),
            )
            ->allowedSorts('name', 'created_at', 'submitted_at')
            ->defaultSort('-created_at')
            ->with(['approvedBy', 'suspendedBy'])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/businesses/index', [
            'businesses' => BusinessData::collect($businesses, PaginatedDataCollection::class),
            'onboardingStatuses' => OnboardingStatus::cases(),
        ]);
    }

    public function create(): Modal
    {
        return Inertia::modal('admin/businesses/create', [
            'ownerMethods' => OwnerMethod::cases(),
            'locationOptions' => LocationOptionsData::current(),
        ])->baseRoute('admin.businesses.index');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, OnboardBusinessData $data, OnboardBusiness $onboard): RedirectResponse
    {
        $business = $onboard->handle($request->user(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business created.')]);

        return to_route('admin.businesses.show', $business);
    }

    /**
     * The Details tab of a business.
     */
    public function show(Business $business): Response
    {
        $business->load(['approvedBy', 'suspendedBy']);

        return Inertia::render('admin/businesses/show', [
            'business' => BusinessData::fromModel($business),
        ]);
    }
}
