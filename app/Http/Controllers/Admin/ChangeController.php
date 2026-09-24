<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Changes\ReviewChange;
use App\Data\Admin\ChangeData;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The change feed: owners' edits to what visitors see, which admins review and can revert.
 */
class ChangeController extends Controller
{
    public function index(Request $request): Response
    {
        $changes = QueryBuilder::for(Activity::query()->content(), $request)
            ->allowedFilters(
                AllowedFilter::callback('status', fn (Builder $query, mixed $value) => match ($value) {
                    'reviewed' => $query->whereNotNull('reviewed_at')->whereNull('reverted_at'),
                    'reverted' => $query->whereNotNull('reverted_at'),
                    'all' => $query,
                    default => $query->whereNull('reviewed_at'),
                })->default('unreviewed'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::exact('event'),
                AllowedFilter::callback('search', fn (Builder $query, mixed $value) => $query->whereHasMorph(
                    'subject',
                    [Outlet::class, Business::class],
                    fn (Builder $subject, string $type) => $subject->where(fn (Builder $subject) => $subject
                        ->whereLike('name', '%'.trim((string) $value).'%')
                        ->when($type === Outlet::class, fn (Builder $outlet) => $outlet->orWhereHas(
                            'business',
                            fn (Builder $business) => $business->whereLike('name', '%'.trim((string) $value).'%'),
                        ))),
                ))->delimiter(''),
            )
            ->allowedSorts('created_at')
            ->defaultSort('-created_at')
            ->with(['causer', 'reviewedBy', 'subject'])
            ->paginate(30)
            ->withQueryString();

        $changes->getCollection()->loadMorph('subject', [Outlet::class => ['business']]);

        return Inertia::render('admin/changes/index', [
            'changes' => ChangeData::collect($changes, PaginatedDataCollection::class),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function review(Request $request, Activity $change, ReviewChange $reviewChange): RedirectResponse
    {
        $reviewChange->review($request->user(), $change);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change marked reviewed.')]);

        return back();
    }

    public function reviewMany(Request $request, ReviewChange $reviewChange): RedirectResponse
    {
        /** @var array{ids: list<int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $count = $reviewChange->reviewMany($request->user(), $validated['ids']);

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(':count change marked reviewed.|:count changes marked reviewed.', $count)]);

        return back();
    }
}
