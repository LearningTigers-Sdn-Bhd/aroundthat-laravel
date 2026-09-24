<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdateMediaDisk;
use App\Data\Forms\MediaSettingsData;
use App\Enums\MediaDisk;
use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * App-wide settings an admin can change without a deploy.
 */
class SettingsController extends Controller
{
    public function edit(Settings $settings): Response
    {
        return Inertia::render('admin/settings/index', [
            'mediaDisk' => $settings->mediaDisk(),
            'mediaDisks' => collect(MediaDisk::cases())->map(fn (MediaDisk $disk): array => [
                'value' => $disk->value,
                'label' => $disk->label(),
                'configured' => $disk->isConfigured(),
            ]),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function updateMedia(MediaSettingsData $data, UpdateMediaDisk $updateMediaDisk): RedirectResponse
    {
        $updateMediaDisk->handle($data->mediaDisk);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Image storage saved.')]);

        return back();
    }
}
