<?php

namespace App\Actions\Images;

use App\Enums\ImageKind;
use App\Models\Business;
use App\Models\Image;
use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Add, describe, order and remove an outlet's photos or a business's logo. Every change is logged on the outlet or
 * business as its list of images. Removing only hides an image; its file is pruned after Image::KEEP_REMOVED_DAYS.
 */
class ManageImages
{
    /**
     * @var list<string>
     */
    public const array CONTENT_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const int MAX_KILOBYTES = 10240;

    public const int MAX_DIMENSION = 8000;

    public function __construct(
        protected AuditTrail $audit,
        protected Settings $settings,
    ) {}

    /**
     * @throws ValidationException
     */
    public function add(Outlet|Business $owner, ImageKind $kind, UploadedFile $file, string $altText): Image
    {
        $this->ensureKindFits($owner, $kind);
        [$width, $height] = $this->measure($file);

        return DB::transaction(function () use ($owner, $kind, $file, $altText, $width, $height): Image {
            $owner = $this->writable($owner);

            return $this->logged($owner, 'image_added', function () use ($owner, $kind, $file, $altText, $width, $height): Image {
                $current = $owner->images()->active()->where('kind', $kind)->get();

                if ($kind->replacesCurrent()) {
                    $current->each(fn (Image $image) => $image->forceFill(['removed_at' => now()])->save());
                } elseif ($current->count() >= $kind->limit()) {
                    throw ValidationException::withMessages([
                        'file' => __('Remove a photo first: an outlet can have at most :limit gallery photos.', ['limit' => $kind->limit()]),
                    ]);
                }

                $disk = $this->settings->mediaDisk()->diskName();
                $path = $file->storePublicly("{$owner->getMorphClass()}/{$owner->getKey()}", ['disk' => $disk]);

                if ($path === false) {
                    throw ValidationException::withMessages(['file' => __('The image could not be stored. Try again.')]);
                }

                $image = $owner->images()->make(['alt_text' => trim($altText), 'position' => (int) $current->max('position') + 1]);
                $image->forceFill(['kind' => $kind, 'disk' => $disk, 'path' => $path, 'width' => $width, 'height' => $height])->save();

                return $image;
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function describe(Image $image, string $altText): Image
    {
        return DB::transaction(function () use ($image, $altText): Image {
            $owner = $this->writable($this->ownerOf($image));

            return $this->logged($owner, 'image_changed', function () use ($image, $altText): Image {
                $image->update(['alt_text' => trim($altText)]);

                return $image;
            });
        });
    }

    /**
     * Put the images of one kind in the given order. Images left out keep their place after them.
     *
     * @param  list<string>  $imageIds
     *
     * @throws ValidationException
     */
    public function reorder(Outlet|Business $owner, ImageKind $kind, array $imageIds): void
    {
        DB::transaction(function () use ($owner, $kind, $imageIds): void {
            $owner = $this->writable($owner);

            $this->logged($owner, 'images_reordered', function () use ($owner, $kind, $imageIds): void {
                foreach (array_values(array_unique($imageIds)) as $position => $imageId) {
                    $owner->images()->active()->where('kind', $kind)->whereKey($imageId)->update(['position' => $position + 1]);
                }
            });
        });
    }

    /**
     * @throws ValidationException
     */
    public function remove(Image $image): void
    {
        DB::transaction(function () use ($image): void {
            $owner = $this->writable($this->ownerOf($image));

            $this->logged($owner, 'image_removed', fn () => $image->forceFill(['removed_at' => now()])->save());
        });
    }

    /**
     * Run a change and log the owner's images before and after it, as "Kind: alt text" lines.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $change
     * @return TReturn
     */
    protected function logged(Outlet|Business $owner, string $event, callable $change): mixed
    {
        $before = $this->imageList($owner);
        $result = $change();
        $after = $this->imageList($owner);

        if ($before !== $after) {
            $this->audit->record($owner, $event, null, ['images' => ['old' => $before, 'new' => $after]]);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    protected function imageList(Outlet|Business $owner): array
    {
        return array_values($owner->images()->active()->orderBy('kind')->orderBy('position')->get()
            ->map(fn (Image $image): string => "{$image->kind->label()}: {$image->alt_text}")
            ->all());
    }

    /**
     * @throws ValidationException
     */
    protected function ensureKindFits(Outlet|Business $owner, ImageKind $kind): void
    {
        $fits = $owner instanceof Business ? $kind === ImageKind::Logo : $kind !== ImageKind::Logo;

        if (! $fits) {
            throw ValidationException::withMessages(['kind' => __('This kind of image does not belong here.')]);
        }
    }

    /**
     * Read the size from the file's own bytes, so a renamed text file is not taken for an image.
     *
     * @return array{0: int, 1: int}
     *
     * @throws ValidationException
     */
    protected function measure(UploadedFile $file): array
    {
        $size = in_array($file->getMimeType(), self::CONTENT_TYPES, true) ? @getimagesize($file->getRealPath()) : false;

        if ($size === false || ! in_array($size['mime'], self::CONTENT_TYPES, true)) {
            throw ValidationException::withMessages(['file' => __('Upload a JPEG, PNG or WebP image.')]);
        }

        if ($size[0] > self::MAX_DIMENSION || $size[1] > self::MAX_DIMENSION) {
            throw ValidationException::withMessages(['file' => __('The image can be at most :max pixels wide or tall.', ['max' => self::MAX_DIMENSION])]);
        }

        return [$size[0], $size[1]];
    }

    /**
     * Lock the owner and check its images can change.
     *
     * @throws ValidationException
     */
    protected function writable(Outlet|Business $owner): Outlet|Business
    {
        $owner = $owner->lockedForUpdate();

        if (! $owner->isWritable()) {
            throw ValidationException::withMessages([
                'file' => $owner instanceof Outlet
                    ? __('This outlet cannot be changed while it is archived, suspended or waiting for review.')
                    : __('This business cannot be changed while it is suspended or waiting for review.'),
            ]);
        }

        return $owner;
    }

    protected function ownerOf(Image $image): Outlet|Business
    {
        $owner = $image->imageable;

        if (! $owner instanceof Outlet && ! $owner instanceof Business) {
            abort(404);
        }

        return $owner;
    }
}
