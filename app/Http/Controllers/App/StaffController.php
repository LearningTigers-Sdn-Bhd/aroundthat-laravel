<?php

namespace App\Http\Controllers\App;

use App\Actions\Staff\ChangeMemberStatus;
use App\Actions\Staff\UpdateMemberAccess;
use App\Data\Forms\MemberAccessData;
use App\Data\Forms\ReasonData;
use App\Data\InvitationData;
use App\Data\MemberData;
use App\Data\OutletOptionData;
use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The owner's staff page: members with their roles and outlets, and open invitations.
 */
class StaffController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function index(Request $request): Response
    {
        $business = $this->workspace->business();

        Gate::authorize('viewAny', [Membership::class, $business]);

        $sortByName = AllowedSort::callback('name', fn (Builder $query, bool $descending) => $query->orderBy(
            User::query()->select('name')->whereColumn('users.id', 'memberships.user_id'),
            $descending ? 'desc' : 'asc',
        ));

        $members = QueryBuilder::for($business->memberships(), $request)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, mixed $value) => $query->whereHas(
                    'user',
                    fn (Builder $user) => $user
                        ->whereLike('name', '%'.trim((string) $value).'%')
                        ->orWhereLike('email', '%'.trim((string) $value).'%'),
                )),
                AllowedFilter::exact('role'),
                AllowedFilter::callback('status', fn (Builder $query, mixed $value) => $value === 'suspended'
                    ? $query->whereNotNull('suspended_at')
                    : $query->whereNull('suspended_at')),
            )
            ->allowedSorts($sortByName)
            ->defaultSort($sortByName)
            ->with(['user', 'outlets'])
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('app/staff/index', [
            'members' => MemberData::collect($members, PaginatedDataCollection::class),
            'invitations' => InvitationData::collect(
                $business->invitations()->open()->with(['invitedBy', 'outlets'])->latest()->get(),
            ),
            'outletOptions' => OutletOptionData::collect($business->outlets()->operational()->orderBy('name')->get()),
            'roles' => MembershipRole::cases(),
            'canInvite' => $request->user()->can('create', [Invitation::class, $business]),
        ]);
    }

    /**
     * Change a member's role and the outlets they work at.
     *
     * @throws ValidationException
     */
    public function update(Membership $membership, MemberAccessData $data, UpdateMemberAccess $updateAccess): RedirectResponse
    {
        $this->authorizeFor($membership);

        $updateAccess->handle($membership, $data->role, $data->outletIds);

        return $this->done(__('Access updated.'));
    }

    /**
     * @throws ValidationException
     */
    public function suspend(Request $request, Membership $membership, ReasonData $data, ChangeMemberStatus $status): RedirectResponse
    {
        $this->authorizeFor($membership);

        $status->suspend($request->user(), $membership, $data->reason);

        return $this->done(__('Member suspended.'));
    }

    /**
     * @throws ValidationException
     */
    public function reactivate(Membership $membership, ChangeMemberStatus $status): RedirectResponse
    {
        $this->authorizeFor($membership);

        $status->reactivate($membership);

        return $this->done(__('Member reactivated.'));
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Membership $membership, ChangeMemberStatus $status): RedirectResponse
    {
        $this->authorizeFor($membership, 'delete');

        $status->remove($membership);

        return $this->done(__('Member removed.'));
    }

    protected function authorizeFor(Membership $membership, string $ability = 'update'): void
    {
        $this->workspace->ensureOwns($membership);
        Gate::authorize($ability, $membership);
    }

    protected function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
