<?php

namespace App\Actions\Settings;

use App\Enums\MediaDisk;
use App\Support\Settings;
use Illuminate\Validation\ValidationException;

/**
 * An admin chooses where new images are stored. Images already uploaded stay on their own disk.
 */
class UpdateMediaDisk
{
    public function __construct(protected Settings $settings) {}

    /**
     * @throws ValidationException
     */
    public function handle(MediaDisk $disk): void
    {
        if (! $disk->isConfigured()) {
            throw ValidationException::withMessages([
                'media_disk' => __(':disk is not set up. Add its keys to the environment first.', ['disk' => $disk->label()]),
            ]);
        }

        $this->settings->setMediaDisk($disk);
    }
}
