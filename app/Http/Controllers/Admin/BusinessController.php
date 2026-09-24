<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Businesses\OnboardBusiness;
use App\Data\Admin\ActivityData;
use App\Data\Admin\BusinessData;
use App\Data\Admin\OutletData;
use App\Data\Forms\OnboardBusinessData;
use App\Data\InvitationData;
use App\Data\LocationOptionsData;
use App\Data\MemberData;
use App\Enums\OnboardingStatus;
use App\Enums\OwnerMethod;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Business;
use App\Support\QueryFilters\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
     * Everything about one business: its details, outlets, staff, open invitations and change history.
     */
    public function show(Business $business): Response
    {
        $business->load(['approvedBy', 'suspendedBy']);

        return Inertia::render('admin/businesses/show', [
            'business' => BusinessData::fromModel($business),
            'outlets' => OutletData::collect(
                $business->outlets()->with(['business', 'hostOutlet', 'approvedBy', 'suspendedBy'])->orderBy('name')->get(),
            ),
            'members' => MemberData::collect(
                $business->memberships()->with(['user', 'outlets'])->get()->sortBy('user.name')->values(),
            ),
            'invitations' => InvitationData::collect(
                $business->invitations()->open()->with(['invitedBy', 'outlets'])->latest()->get(),
            ),
            'activities' => Inertia::defer(fn () => ActivityData::collect($this->activities($business))),
        ]);
    }

    /**
     * The latest changes to the business and to its outlets, members and invitations.
     *
     * @return Collection<int, Activity>
     */
    protected function activities(Business $business): Collection
    {
        $subjects = [
            'business' => [$business->id],
            'outlet' => $business->outlets()->pluck('id')->all(),
            'membership' => $business->memberships()->pluck('id')->all(),
            'invitation' => $business->invitations()->pluck('id')->all(),
        ];

        return Activity::query()
            ->where(function (Builder $query) use ($subjects): void {
                foreach ($subjects as $type => $ids) {
                    $query->orWhere(fn (Builder $query) => $query->where('subject_type', $type)->whereIn('subject_id', $ids));
                }
            })
            ->with('causer')
            ->latest('id')
            ->limit(100)
            ->get();
    }
}
