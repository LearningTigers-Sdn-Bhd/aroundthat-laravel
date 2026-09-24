<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tags\ManageTag;
use App\Data\Admin\TagData;
use App\Data\Forms\ReasonData;
use App\Data\Forms\TagData as TagFormData;
use App\Enums\TagStatus;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\QueryFilters\SearchFilter;
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
 * The shared tag list: admins curate it and review the tags owners create.
 */
class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $tags = QueryBuilder::for(Tag::class, $request)
            ->allowedFilters(
                SearchFilter::on(['name', 'slug']),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('merged', fn (Builder $query, mixed $value) => $value === true
                    ? $query->whereNotNull('merged_into_id')
                    : $query->whereNull('merged_into_id')),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->with(['createdByBusiness', 'mergedInto'])
            ->withCount('outlets')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/tags/index', [
            'tags' => TagData::collect($tags, PaginatedDataCollection::class),
            'mergeTargets' => Tag::query()
                ->where('status', TagStatus::Approved)
                ->whereNull('merged_into_id')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Tag $tag): array => ['value' => $tag->id, 'label' => $tag->name]),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(TagFormData $data, ManageTag $manageTag): RedirectResponse
    {
        $manageTag->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag added.')]);

        return back();
    }

    public function update(Tag $tag, TagFormData $data, ManageTag $manageTag): RedirectResponse
    {
        $manageTag->update($tag, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag saved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function approve(Tag $tag, ManageTag $manageTag): RedirectResponse
    {
        $manageTag->approve($tag);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag approved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function reject(Tag $tag, ReasonData $data, ManageTag $manageTag): RedirectResponse
    {
        $manageTag->reject($tag, $data->reason);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag rejected and removed from its outlets.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function merge(Request $request, Tag $tag, ManageTag $manageTag): RedirectResponse
    {
        /** @var array{target_id: string} $validated */
        $validated = $request->validate(['target_id' => ['required', 'uuid', 'exists:tags,id']]);

        $manageTag->merge($tag, Tag::query()->findOrFail($validated['target_id']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag merged.')]);

        return back();
    }
}
