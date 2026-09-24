<?php

namespace App\Http\Controllers\App;

use App\Actions\Outlets\UpdateOutletPublicProfile;
use App\Data\CategoryOptionData;
use App\Data\Forms\OutletPublicProfileData;
use App\Data\OutletData;
use App\Data\PlaceProfileData;
use App\Data\RevertNoticeData;
use App\Data\TagOptionData;
use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Tag;
use App\Support\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The outlet's public page tab: what visitors see beyond the details the outlet already has.
 */
class OutletPublicProfileController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function edit(Request $request, Outlet $outlet): Response
    {
        $this->workspace->ensureOwns($outlet);
        $this->workspace->authorize(Ability::ManagePublicContent);

        $outlet->load(['business', 'hostOutlet', 'category', 'tags']);

        return Inertia::render('app/outlets/public', [
            'outlet' => OutletData::fromModel($outlet),
            'recentReverts' => RevertNoticeData::recentFor($outlet),
            'place' => PlaceProfileData::fromModel($outlet),
            'categories' => CategoryOptionData::collect(
                Category::query()
                    ->where(fn (Builder $query) => $query->active()->orWhere('id', $outlet->category_id))
                    ->ordered()
                    ->get(),
            ),
            'tagOptions' => TagOptionData::collect(
                Tag::query()
                    ->where(fn (Builder $query) => $query
                        ->pickableBy($outlet->business)
                        ->orWhereIn('id', $outlet->tags->modelKeys()))
                    ->orderBy('name')
                    ->get(),
            ),
            'can' => [
                'update' => $request->user()->can('updatePublicProfile', $outlet),
                'archive' => $request->user()->can('archive', $outlet),
                'submit' => $request->user()->can('submit', $outlet),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(Outlet $outlet, OutletPublicProfileData $data, UpdateOutletPublicProfile $updatePublicProfile): RedirectResponse
    {
        $this->workspace->ensureOwns($outlet);
        Gate::authorize('updatePublicProfile', $outlet);

        $updatePublicProfile->handle($outlet, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Public page saved.')]);

        return back();
    }
}
