<?php

namespace App\Actions\Tags;

use App\Data\Forms\TagData;
use App\Enums\TagStatus;
use App\Models\Outlet;
use App\Models\Tag;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An admin curates the shared tag list: adds and renames tags, reviews the ones owners created, and merges duplicates.
 */
class ManageTag
{
    public function __construct(
        protected AuditTrail $audit,
        protected SyncOutletTags $syncOutletTags,
    ) {}

    /**
     * @throws ValidationException
     */
    public function create(TagData $data): Tag
    {
        $slug = Tag::slugFor($data->name);

        if ($slug === '') {
            throw ValidationException::withMessages(['name' => __('The name must contain a letter or a digit.')]);
        }

        if (Tag::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => __('A tag with this name already exists.')]);
        }

        $tag = new Tag($data->toModelAttributes());
        $tag->forceFill(['slug' => $slug, 'status' => TagStatus::Approved])->save();

        return $tag;
    }

    public function update(Tag $tag, TagData $data): Tag
    {
        $tag->update($data->toModelAttributes());

        return $tag;
    }

    /**
     * @throws ValidationException
     */
    public function approve(Tag $tag): Tag
    {
        return DB::transaction(function () use ($tag): Tag {
            $tag = $this->reviewable($tag);

            return $this->audit->as('approved', null, function () use ($tag): Tag {
                $tag->forceFill(['status' => TagStatus::Approved])->save();

                return $tag;
            });
        });
    }

    /**
     * Reject the tag and take it off every outlet that carries it. It can no longer be picked or typed.
     *
     * @throws ValidationException
     */
    public function reject(Tag $tag, string $reason): Tag
    {
        return DB::transaction(function () use ($tag, $reason): Tag {
            $tag = $this->reviewable($tag);

            return $this->audit->as('rejected', $reason, function () use ($tag, $reason): Tag {
                $tag->forceFill(['status' => TagStatus::Rejected])->save();

                $tag->outlets()->get()->each(fn (Outlet $outlet) => $this->syncOutletTags->handle(
                    $outlet,
                    $this->tagIdsWithout($outlet, $tag),
                    'tag_rejected',
                    $reason,
                ));

                return $tag;
            });
        });
    }

    /**
     * Fold a duplicate into another tag: its outlets carry the target instead, and typing its name finds the target.
     *
     * @throws ValidationException
     */
    public function merge(Tag $tag, Tag $target): Tag
    {
        if ($tag->is($target) || $target->isMerged() || $target->status === TagStatus::Rejected) {
            throw ValidationException::withMessages(['target_id' => __('Choose another tag to merge into.')]);
        }

        return DB::transaction(function () use ($tag, $target): Tag {
            $tag = $tag->lockedForUpdate();

            if ($tag->isMerged()) {
                throw ValidationException::withMessages(['tag' => __('This tag has already been merged.')]);
            }

            return $this->audit->as('merged', null, function () use ($tag, $target): Tag {
                $tag->outlets()->get()->each(fn (Outlet $outlet) => $this->syncOutletTags->handle(
                    $outlet,
                    array_values(array_unique([...$this->tagIdsWithout($outlet, $tag), $target->id])),
                    'tag_merged',
                ));

                $tag->forceFill(['merged_into_id' => $target->getKey(), 'is_active' => false])->save();

                return $tag;
            });
        });
    }

    /**
     * @return list<string>
     */
    protected function tagIdsWithout(Outlet $outlet, Tag $tag): array
    {
        return array_values($outlet->tags
            ->reject(fn (Tag $carried): bool => $carried->is($tag))
            ->map(fn (Tag $carried): string => $carried->id)
            ->all());
    }

    /**
     * @throws ValidationException
     */
    protected function reviewable(Tag $tag): Tag
    {
        $tag = $tag->lockedForUpdate();

        if ($tag->status !== TagStatus::Pending || $tag->isMerged()) {
            throw ValidationException::withMessages(['tag' => __('This tag is not waiting for review.')]);
        }

        return $tag;
    }
}
