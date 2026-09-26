<?php

namespace App\Http\Controllers\App;

use App\Actions\Images\ManageImages;
use App\Data\Forms\ImageUploadData;
use App\Data\ImageData;
use App\Data\OutletData;
use App\Data\RevertNoticeData;
use App\Enums\Ability;
use App\Enums\ImageKind;
use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\Outlet;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The outlet's photos tab: one cover photo and a gallery.
 */
class OutletPhotoController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    public function index(Request $request, Outlet $outlet): Response
    {
        $this->workspace->ensureOwns($outlet);
        $this->workspace->authorize(Ability::ManagePublicContent);

        $outlet->load(['business', 'hostOutlet']);

        return Inertia::render('app/outlets/photos', [
            'outlet' => OutletData::fromModel($outlet),
            'recentReverts' => RevertNoticeData::recentFor($outlet),
            'images' => ImageData::collect($outlet->images()->active()->orderBy('position')->get()),
            'galleryLimit' => ImageKind::Gallery->limit(),
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
    public function store(Outlet $outlet, ImageUploadData $data, ManageImages $images): RedirectResponse
    {
        $this->authorizeChange($outlet);

        $images->add($outlet, $data->kind, $data->file, $data->altText);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo added.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request, Outlet $outlet, Image $image, ManageImages $images): RedirectResponse
    {
        $this->authorizeChange($outlet, $image);

        /** @var array{alt_text: string} $validated */
        $validated = $request->validate(['alt_text' => ['required', 'string', 'max:250']]);

        $images->describe($image, $validated['alt_text']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo description saved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function reorder(Request $request, Outlet $outlet, ManageImages $images): RedirectResponse
    {
        $this->authorizeChange($outlet);

        /** @var array{ids: list<string>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $images->reorder($outlet, ImageKind::Gallery, $validated['ids']);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function destroy(Outlet $outlet, Image $image, ManageImages $images): RedirectResponse
    {
        $this->authorizeChange($outlet, $image);

        $images->remove($image);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo removed.')]);

        return back();
    }

    protected function authorizeChange(Outlet $outlet, ?Image $image = null): void
    {
        $this->workspace->ensureOwns($outlet);

        if ($image && ($image->imageable_type !== $outlet->getMorphClass() || $image->imageable_id !== $outlet->id || $image->removed_at)) {
            abort(404);
        }

        Gate::authorize('updatePublicProfile', $outlet);
    }
}
