<?php

namespace App\Actions\Tags;

use App\Models\Outlet;
use App\Support\ActivityLog\AuditTrail;

/**
 * Set an outlet's tags and log the change on the outlet as tag names, old and new.
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

        $outlet->tags()->sync($tagIds);

        $names = $this->tagNames($outlet);

        if ($previousNames !== $names) {
            $this->audit->record($outlet, $event, $reason, [
                'tags' => ['old' => $previousNames, 'new' => $names],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    protected function tagNames(Outlet $outlet): array
    {
        return array_values($outlet->tags()->pluck('name')->all());
    }
}
