<?php

namespace App\Actions\Tags;

use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;

/**
 * Set an outlet's tags and log the change on the outlet as tag names, old and new.
 * The tag ids are kept under `restore`, so an admin can revert the change.
 */
class SyncOutletTags
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @param  list<string>  $tagIds
     */
    public function handle(Outlet $outlet, array $tagIds, string $event = 'tags_changed', ?string $reason = null): void
    {
        $previousNames = $this->tagNames($outlet);
        $previousIds = $this->tagIds($outlet);

        $outlet->tags()->sync($tagIds);

        $names = $this->tagNames($outlet);

        if ($previousNames !== $names) {
            $this->audit->record($outlet, $event, $reason, [
                'tags' => ['old' => $previousNames, 'new' => $names],
                'restore' => ['tag_ids' => ['old' => $previousIds, 'new' => $this->tagIds($outlet)]],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    protected function tagIds(Outlet $outlet): array
    {
        return array_values($outlet->tags()->orderBy('tags.id')->pluck('tags.id')->all());
    }

    /**
     * @return list<string>
     */
    protected function tagNames(Outlet $outlet): array
    {
        return array_values($outlet->tags()->pluck('name')->all());
    }
}
