<?php

namespace App\Actions\Changes;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Mark owners' edits to public content as seen by an admin. Marking a change twice keeps the first review.
 */
class ReviewChange
{
    /**
     * @throws ValidationException
     */
    public function review(User $admin, Activity $change): Activity
    {
        if (! $change->isContent()) {
            throw ValidationException::withMessages(['change' => __('Only changes to public content are reviewed.')]);
        }

        if ($change->reviewed_at === null) {
            $change->forceFill(['reviewed_at' => now(), 'reviewed_by_id' => $admin->id])->save();
        }

        return $change;
    }

    /**
     * Mark the given changes reviewed, skipping any that are not content changes or were reviewed already.
     *
     * @param  list<int>  $changeIds
     * @return int How many changes were marked.
     */
    public function reviewMany(User $admin, array $changeIds): int
    {
        return Activity::query()
            ->content()
            ->unreviewed()
            ->whereKey($changeIds)
            ->update(['reviewed_at' => now(), 'reviewed_by_id' => $admin->id]);
    }
}
