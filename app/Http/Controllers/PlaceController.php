<?php

namespace App\Http\Controllers;

use App\Data\PlacePreviewData;
use App\Enums\TagStatus;
use App\Models\Outlet;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An outlet's public page, open to everyone. Outlets visitors cannot see return 404, and only approved tags show.
 */
class PlaceController extends Controller
{
    public function show(string $slug): Response
    {
        $outlet = Outlet::query()->public()->where('slug', $slug)->with([
            'tags' => fn ($tags) => $tags->where('tags.status', TagStatus::Approved)->whereNull('tags.merged_into_id'),
        ])->firstOrFail();

        return Inertia::render('places/show', [
            'place' => PlacePreviewData::fromModel($outlet, now()),
        ]);
    }
}
