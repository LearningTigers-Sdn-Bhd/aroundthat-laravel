<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\ActivityData;
use App\Data\Admin\MembershipData;
use App\Data\Admin\UserData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use App\Support\QueryFilters\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = QueryBuilder::for(User::class, $request)
            ->allowedFilters(
                SearchFilter::on(['name', 'email']),
                AllowedFilter::callback('suspended', fn (Builder $query, mixed $value) => $value === true
                    ? $query->whereNotNull('suspended_at')
                    : $query->whereNull('suspended_at')),
                AllowedFilter::exact('is_admin'),
            )
            ->allowedSorts('name', 'email', 'created_at', 'last_login_at')
            ->defaultSort('name')
            ->with('suspendedBy')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => UserData::collect($users, PaginatedDataCollection::class),
        ]);
    }

    /**
     * One login: its account state, the businesses it belongs to, and its change history.
     */
    public function show(User $user): Response
    {
        $user->load('suspendedBy');

        return Inertia::render('admin/users/show', [
            'user' => UserData::fromModel($user),
            'memberships' => MembershipData::collect(
                $user->memberships()->with(['business', 'outlets'])->get()->sortBy('business.name')->values(),
            ),
            'activities' => Inertia::defer(fn () => ActivityData::collect(
                Activity::forSubject($user)->with('causer')->latest('id')->limit(100)->get(),
            )),
        ]);
    }
}
