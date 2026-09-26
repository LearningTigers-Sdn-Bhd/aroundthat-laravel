<?php

namespace App\Actions\Outlets;

use App\Actions\Tags\FindOrCreateTag;
use App\Actions\Tags\SyncOutletTags;
use App\Data\Forms\OutletLinksData;
use App\Data\Forms\OutletListingData;
use App\Data\Forms\OutletLocationData;
use App\Data\Forms\OutletPublicProfileData;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\Outlet;
use App\Models\Tag;
use App\Support\ActivityLog\AuditTrail;
use App\Support\GoogleMapsLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Save one part of what visitors see about an outlet: its description, map location, links or listing. It goes live
 * at once; the change log keeps the old values.
 */
class UpdateOutletPublicProfile
{
    /**
     * The field names missingForListing() returns, as owners read them.
     */
    protected const array LISTING_FIELDS = [
        'summary' => 'a summary',
        'category_id' => 'a category',
        'coordinates' => 'the map location',
        'hours' => 'opening hours',
    ];

    public function __construct(
        protected AuditTrail $audit,
        protected FindOrCreateTag $findOrCreateTag,
        protected SyncOutletTags $syncOutletTags,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Outlet $outlet, OutletPublicProfileData|OutletLocationData|OutletLinksData|OutletListingData $data): Outlet
    {
        return DB::transaction(function () use ($outlet, $data): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if (! $outlet->isWritable()) {
                throw ValidationException::withMessages([
                    'outlet' => __('This outlet cannot be changed while it is archived, suspended or waiting for review.'),
                ]);
            }

            $missingBefore = $outlet->missingForListing();

            $outlet->fill(match (true) {
                $data instanceof OutletPublicProfileData => [
                    'summary' => $data->summary,
                    'description' => $data->description,
                    'category_id' => $this->categoryId($outlet, $data->categoryId),
                ],
                $data instanceof OutletLocationData => $this->coordinates($data),
                $data instanceof OutletLinksData => [
                    'website' => $data->website,
                    'whatsapp' => $data->whatsapp,
                    'facebook' => $data->facebook,
                    'instagram' => $data->instagram,
                ],
                $data instanceof OutletListingData => ['is_listed' => $data->isListed],
            });

            $this->ensureListable($outlet, $data instanceof OutletListingData, $missingBefore);

            $tagIds = $data instanceof OutletPublicProfileData ? $this->tagIds($outlet, $data->tags ?? []) : null;
            $previousCategoryId = $outlet->getOriginal('category_id');

            return $this->audit->contentChange('public_profile_changed', function () use ($outlet, $tagIds, $previousCategoryId): Outlet {
                $outlet->save();

                if ($previousCategoryId !== $outlet->category_id) {
                    $this->audit->record($outlet, 'category_changed', null, [
                        'category' => [
                            'old' => $previousCategoryId ? Category::query()->whereKey($previousCategoryId)->value('name') : null,
                            'new' => $outlet->category()->value('name'),
                        ],
                        'restore' => ['category_id' => ['old' => $previousCategoryId, 'new' => $outlet->category_id]],
                    ]);
                }

                if ($tagIds !== null) {
                    $this->syncOutletTags->handle($outlet, $tagIds);
                }

                return $outlet;
            });
        });
    }

    /**
     * Listing needs every public field, and is refused while an admin hides the outlet. Once listed, other saves cannot
     * remove a field the listing needs, but gaps they did not cause do not block them.
     *
     * @param  list<string>  $missingBefore
     *
     * @throws ValidationException
     */
    protected function ensureListable(Outlet $outlet, bool $isListingChange, array $missingBefore): void
    {
        if ($isListingChange && $outlet->is_listed && ! $outlet->getOriginal('is_listed') && $outlet->isHidden()) {
            throw ValidationException::withMessages([
                'is_listed' => __('An admin hid this outlet from the public listing. It can be listed again once an admin unhides it.'),
            ]);
        }

        if (! $outlet->is_listed) {
            return;
        }

        $missing = $isListingChange
            ? $outlet->missingForListing()
            : array_values(array_diff($outlet->missingForListing(), $missingBefore));

        if ($missing === []) {
            return;
        }

        $fields = collect($missing)->map(fn (string $field): string => __(self::LISTING_FIELDS[$field]))->join(', ', ' and ');

        throw ValidationException::withMessages([
            'is_listed' => $isListingChange
                ? __('Add :fields before listing the outlet.', ['fields' => $fields])
                : __('A listed outlet needs :fields. Unlist it on the Details tab first.', ['fields' => $fields]),
        ]);
    }

    /**
     * A hidden category stays on an outlet that already has it, but cannot be newly picked.
     *
     * @throws ValidationException
     */
    protected function categoryId(Outlet $outlet, ?string $categoryId): ?string
    {
        if ($categoryId === null || $categoryId === $outlet->category_id) {
            return $categoryId;
        }

        if (! Category::query()->active()->whereKey($categoryId)->exists()) {
            throw ValidationException::withMessages(['category_id' => __('Choose one of the listed categories.')]);
        }

        return $categoryId;
    }

    /**
     * A Google Maps link wins over typed coordinates, because it is what the owner pasted last.
     *
     * @return array{google_maps_url: string|null, latitude: float|null, longitude: float|null}
     *
     * @throws ValidationException
     */
    protected function coordinates(OutletLocationData $data): array
    {
        $link = filled($data->googleMapsUrl) ? trim((string) $data->googleMapsUrl) : null;

        if ($link === null) {
            return ['google_maps_url' => null, 'latitude' => $data->latitude, 'longitude' => $data->longitude];
        }

        try {
            return ['google_maps_url' => $link, ...GoogleMapsLink::coordinates($link)];
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['google_maps_url' => $exception->getMessage()]);
        }
    }

    /**
     * Resolve typed tag names. Tags the outlet already carries stay even if an admin has since hidden them.
     *
     * @param  list<string>  $names
     * @return list<string>
     *
     * @throws ValidationException
     */
    protected function tagIds(Outlet $outlet, array $names): array
    {
        $carried = $outlet->tags()->pluck('tags.id')->all();
        $tagIds = [];

        foreach ($names as $name) {
            if (trim($name) === '') {
                continue;
            }

            $tag = $this->findOrCreateTag->handle($outlet->business, $name);
            $isCarried = in_array($tag->id, $carried, true);

            if (! $isCarried && ($tag->status === TagStatus::Rejected || ! $tag->is_active)) {
                throw ValidationException::withMessages(['tags' => __('“:name” is not available as a tag.', ['name' => trim($name)])]);
            }

            $tagIds[] = $tag->id;
        }

        $tagIds = array_values(array_unique($tagIds));

        if (count($tagIds) > Tag::MAX_PER_OUTLET) {
            throw ValidationException::withMessages(['tags' => __('An outlet can have at most :max tags.', ['max' => Tag::MAX_PER_OUTLET])]);
        }

        return $tagIds;
    }
}
