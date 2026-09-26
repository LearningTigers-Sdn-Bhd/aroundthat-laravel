<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\TagResource;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Tag;
use App\Support\Locations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * @tags Lookups
 *
 * The values the outlet filters accept. They change rarely, so cache them.
 */
class LookupController extends Controller
{
    /**
     * List categories
     *
     * Categories owners can pick, plus any retired category a public outlet still uses. Filter outlets with
     * `filter[category]=<slug>`.
     */
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->orWhereIn('id', Outlet::query()->public()->select('outlets.category_id')))
            ->withCount(['outlets as outlet_count' => fn (Builder $outlets) => $outlets->whereIn('outlets.id', $this->publicOutletIds())])
            ->ordered()
            ->get();

        return CategoryResource::collection($categories);
    }

    /**
     * List tags
     *
     * Approved tags owners can pick, plus any retired tag a public outlet still uses. Filter outlets with
     * `filter[tag]=<slug>,<slug>`; an outlet must have every listed tag.
     */
    public function tags(): AnonymousResourceCollection
    {
        $tags = Tag::query()
            ->public()
            ->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->orWhereHas('outlets', fn (Builder $outlets) => $outlets->whereIn('outlets.id', $this->publicOutletIds())))
            ->withCount(['outlets as outlet_count' => fn (Builder $outlets) => $outlets->whereIn('outlets.id', $this->publicOutletIds())])
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    /**
     * @return Builder<Outlet>
     */
    protected function publicOutletIds(): Builder
    {
        return Outlet::query()->public()->select('outlets.id');
    }

    /**
     * List states
     *
     * The Malaysian states and federal territories. Filter outlets with `filter[state]=<slug>`.
     */
    public function states(): JsonResponse
    {
        /** @var array<string, int> $counts */
        $counts = Outlet::query()->public()->toBase()
            ->selectRaw('outlets.state, count(*) as outlet_count')
            ->groupBy('outlets.state')
            ->pluck('outlet_count', 'state')
            ->all();

        return response()->json([
            'data' => array_map(fn (string $state): array => [
                'slug' => Str::slug($state),
                'name' => $state,
                'outlet_count' => (int) ($counts[$state] ?? 0),
            ], Locations::MALAYSIAN_STATES),
        ]);
    }
}
