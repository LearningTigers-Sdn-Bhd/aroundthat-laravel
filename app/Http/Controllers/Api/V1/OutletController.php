<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ImageKind;
use App\Enums\TagStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OutletDetailResource;
use App\Http\Resources\OutletResource;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Tag;
use App\Support\Locations;
use App\Support\QueryFilters\SearchFilter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Outlets
 *
 * Outlets visitors can see: approved, listed by their owner, complete, and not hidden by an admin.
 * An outlet that stops being public is left out of the list and returns 404.
 */
class OutletController extends Controller
{
    public const int DEFAULT_PER_PAGE = 25;

    public const int MAX_PER_PAGE = 100;

    /**
     * RFC 3339 times, with or without fractions of a second. The timezone is required.
     */
    protected const string TIMESTAMP_FORMATS = 'Y-m-d\\TH:i:sP,Y-m-d\\TH:i:s.uP';

    /**
     * List outlets
     *
     * Cursor-paginated: follow `links.next` (or pass `meta.next_cursor` as `cursor`) until it is null.
     *
     * Filters take slugs from the lookup endpoints, separated by commas: `filter[category]` matches any listed category,
     * `filter[tag]` needs every listed tag, and `filter[state]` matches any listed state. `filter[search]` matches the
     * name or summary. To sync, pass `filter[updated_since]` with the time of your last sync and `sort=updated_at`.
     * An outlet that stops being public never appears in a sync, so run a full sync now and then to drop those.
     *
     * @throws ValidationException
     */
    #[QueryParameter('filter[category]', description: 'Category slugs, separated by commas. Matches any.', type: 'string', example: 'cafe,restaurant')]
    #[QueryParameter('filter[tag]', description: 'Tag slugs, separated by commas. Matches outlets with every tag.', type: 'string', example: 'halal,wifi')]
    #[QueryParameter('filter[state]', description: 'State slugs, separated by commas. Matches any.', type: 'string', example: 'sabah')]
    #[QueryParameter('filter[search]', description: 'Part of the name or summary.', type: 'string')]
    #[QueryParameter('filter[updated_since]', description: 'An RFC 3339 time with a timezone. Only outlets changed since then.', type: 'string', format: 'date-time', example: '2026-09-01T00:00:00Z')]
    #[QueryParameter('sort', description: '`name` (default) or `updated_at`. Prefix with `-` for descending.', type: 'string', default: 'name')]
    #[QueryParameter('per_page', description: 'Outlets per page, 1 to 100.', type: 'integer', default: self::DEFAULT_PER_PAGE)]
    #[QueryParameter('cursor', description: 'The `meta.next_cursor` of the previous page.', type: 'string')]
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $this->validateQuery($request);

        $outlets = QueryBuilder::for(Outlet::query()->public(), $request)
            ->allowedFilters(
                AllowedFilter::callback('category', fn (Builder $query, mixed $slugs) => $query
                    ->whereIn('outlets.category_id', Category::query()->whereIn('slug', (array) $slugs)->select('id'))),
                AllowedFilter::callback('tag', function (Builder $query, mixed $slugs): void {
                    foreach ((array) $slugs as $slug) {
                        $query->whereHas('tags', fn (Builder $tags) => $tags->where('tags.slug', $slug)->where('tags.status', TagStatus::Approved));
                    }
                }),
                AllowedFilter::callback('state', fn (Builder $query, mixed $slugs) => $query
                    ->whereIn('outlets.state', array_values(array_intersect_key($this->statesBySlug(), array_flip((array) $slugs))))),
                AllowedFilter::callback('updated_since', fn (Builder $query, mixed $since) => $query
                    ->where('outlets.updated_at', '>=', Carbon::parse((string) $since)->utc()))
                    ->delimiter(''),
                SearchFilter::on(['outlets.name', 'outlets.summary']),
            )
            ->allowedSorts('name', 'updated_at')
            ->defaultSort('name')
            ->orderBy('outlets.id')
            ->with([
                'category',
                'business',
                'tags' => fn ($tags) => $tags->where('tags.status', TagStatus::Approved)->whereNull('tags.merged_into_id'),
                'images' => fn ($images) => $images->whereNull('removed_at')->where('kind', ImageKind::Cover),
                'dateExceptions' => fn ($exceptions) => $exceptions->where('date', '>=', now()->subDay()->toDateString()),
            ])
            ->cursorPaginate($validated['per_page'] ?? self::DEFAULT_PER_PAGE)
            ->withQueryString();

        return OutletResource::collection($outlets);
    }

    /**
     * Show an outlet
     */
    public function show(string $slug): OutletDetailResource
    {
        $outlet = Outlet::query()->public()->where('slug', $slug)->with([
            'category',
            'business.images' => fn ($images) => $images->whereNull('removed_at')->where('kind', ImageKind::Logo),
            'tags' => fn ($tags) => $tags->where('tags.status', TagStatus::Approved)->whereNull('tags.merged_into_id'),
            'images' => fn ($images) => $images->whereNull('removed_at')
                ->whereIn('kind', [ImageKind::Cover, ImageKind::Gallery])
                ->orderBy('kind')
                ->orderBy('position'),
            'dateExceptions' => fn ($exceptions) => $exceptions->where('date', '>=', now()->subDay()->toDateString()),
        ])->firstOrFail();

        return new OutletDetailResource($outlet);
    }

    /**
     * Refuse unknown filter values instead of returning an empty list, so a partner notices a typo.
     *
     * @return array{per_page?: int}
     *
     * @throws ValidationException
     */
    protected function validateQuery(Request $request): array
    {
        $filter = $request->input('filter', []);
        $filter = is_array($filter) ? $filter : ['invalid'];

        foreach (['category', 'tag', 'state'] as $name) {
            if (isset($filter[$name]) && is_string($filter[$name])) {
                $filter[$name] = array_values(array_filter(explode(',', $filter[$name])));
            }
        }

        /** @var array{per_page?: int} */
        return validator(
            ['filter' => $filter, 'per_page' => $request->input('per_page')],
            [
                'filter' => ['array'],
                'filter.category' => ['sometimes', 'array'],
                'filter.category.*' => [Rule::exists(Category::class, 'slug')],
                'filter.tag' => ['sometimes', 'array'],
                'filter.tag.*' => [Rule::exists(Tag::class, 'slug')->where('status', TagStatus::Approved->value)->whereNull('merged_into_id')],
                'filter.state' => ['sometimes', 'array'],
                'filter.state.*' => [Rule::in(array_keys($this->statesBySlug()))],
                'filter.updated_since' => ['sometimes', 'string', 'date_format:'.self::TIMESTAMP_FORMATS],
                'filter.search' => ['sometimes', 'string', 'max:100'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            ],
            [
                'filter.category.*' => __('Unknown category. See /api/v1/categories.'),
                'filter.tag.*' => __('Unknown tag. See /api/v1/tags.'),
                'filter.state.*' => __('Unknown state. See /api/v1/states.'),
                'filter.updated_since.date_format' => __('Use an RFC 3339 time with a timezone, such as 2026-09-01T00:00:00Z.'),
            ],
        )->validate();
    }

    /**
     * @return array<string, string>
     */
    protected function statesBySlug(): array
    {
        return collect(Locations::MALAYSIAN_STATES)->keyBy(fn (string $state): string => Str::slug($state))->all();
    }
}
