<?php

namespace App\Http\Controllers\App;

use App\Actions\Businesses\UpdateBusinessPublicProfile;
use App\Actions\Images\ManageImages;
use App\Data\Forms\BusinessPublicProfileData;
use App\Data\Forms\ImageUploadData;
use App\Enums\ImageKind;
use App\Http\Controllers\Controller;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * The business's public summary, description and logo, shown on the business page.
 */
class BusinessPublicProfileController extends Controller
{
    public function __construct(protected Workspace $workspace) {}

    /**
     * @throws ValidationException
     */
    public function update(BusinessPublicProfileData $data, UpdateBusinessPublicProfile $updatePublicProfile): RedirectResponse
    {
        Gate::authorize('updatePublicProfile', $this->workspace->business());

        $updatePublicProfile->handle($this->workspace->business(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Public profile saved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function storeLogo(ImageUploadData $data, ManageImages $images): RedirectResponse
    {
        Gate::authorize('updatePublicProfile', $this->workspace->business());

        $images->add($this->workspace->business(), ImageKind::Logo, $data->file, $data->altText);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Logo saved.')]);

        return back();
    }

    /**
     * @throws ValidationException
     */
    public function destroyLogo(ManageImages $images): RedirectResponse
    {
        $business = $this->workspace->business();
        Gate::authorize('updatePublicProfile', $business);

        $logo = $business->images()->active()->where('kind', ImageKind::Logo)->firstOrFail();
        $images->remove($logo);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Logo removed.')]);

        return back();
    }
}
