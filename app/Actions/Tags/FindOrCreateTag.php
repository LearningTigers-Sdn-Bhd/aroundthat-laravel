<?php

namespace App\Actions\Tags;

use App\Enums\TagStatus;
use App\Models\Business;
use App\Models\Tag;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turn a tag name an owner typed into a tag. Spellings with the same slug are one tag, merged tags lead to the
 * tag they were merged into, and a new name becomes a pending tag for an admin to review.
 */
class FindOrCreateTag
{
    /**
     * @throws ValidationException
     */
    public function handle(Business $business, string $name): Tag
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        $slug = Tag::slugFor($name);

        if ($slug === '') {
            throw ValidationException::withMessages(['tags' => __('A tag must contain a letter or a digit.')]);
        }

        if (mb_strlen($name) > 40) {
            throw ValidationException::withMessages(['tags' => __('A tag can be at most 40 characters.')]);
        }

        $tag = Tag::query()->where('slug', $slug)->first() ?? $this->create($business, $name, $slug);

        return $this->followMerges($tag);
    }

    protected function create(Business $business, string $name, string $slug): Tag
    {
        // A savepoint, so losing the race to another owner does not abort the caller's transaction.
        try {
            return DB::transaction(function () use ($business, $name, $slug): Tag {
                $tag = new Tag(['name' => $name]);
                $tag->forceFill([
                    'slug' => $slug,
                    'status' => TagStatus::Pending,
                    'is_active' => true,
                    'created_by_business_id' => $business->getKey(),
                ])->save();

                return $tag;
            });
        } catch (UniqueConstraintViolationException) {
            return Tag::query()->where('slug', $slug)->firstOrFail();
        }
    }

    protected function followMerges(Tag $tag): Tag
    {
        for ($hops = 0; $tag->isMerged() && $hops < 10; $hops++) {
            $tag = $tag->mergedInto()->firstOrFail();
        }

        return $tag;
    }
}
