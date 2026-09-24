<?php

namespace App\Actions\Changes;

use App\Actions\Images\ManageImages;
use App\Actions\Tags\SyncOutletTags;
use App\Enums\ImageKind;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Category;
use App\Models\Image;
use App\Models\Outlet;
use App\Models\OutletDateException;
use App\Models\User;
use App\Support\ActivityLog\AuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Undo an owner's edit to public content: put back every old value the change log kept for it.
 * A change can only be undone while its new values are still in place; a later edit to the same things
 * has to be reverted first. The revert is logged as its own change, with the admin's reason.
 */
class RevertChange
{
    public function __construct(
        protected AuditTrail $audit,
        protected SyncOutletTags $syncOutletTags,
        protected ManageImages $manageImages,
    ) {}

    /**
     * Why the change cannot be reverted now, as sentences for the admin. Empty when it can.
     *
     * @return list<string>
     */
    public function conflicts(Activity $change): array
    {
        if (! $change->isContent()) {
            return [__('Only changes to public content can be reverted.')];
        }

        if ($change->isReverted()) {
            return [__('This change was already reverted.')];
        }

        $subject = $change->subject;

        if (! $subject instanceof Outlet && ! $subject instanceof Business) {
            return [__('The outlet or business no longer exists.')];
        }

        $fields = $this->fieldChanges($change, $subject);
        $restore = $change->properties?->get('restore') ?? [];

        if ($fields === [] && $restore === []) {
            return [__('This change did not keep enough detail to be reverted.')];
        }

        return array_values(array_filter([
            ...$this->fieldConflicts($subject, $fields),
            ...$this->restoreConflicts($subject, $restore),
        ]));
    }

    /**
     * @throws ValidationException
     */
    public function revert(User $admin, Activity $change, string $reason): Activity
    {
        return DB::transaction(function () use ($admin, $change, $reason): Activity {
            $change = Activity::query()->lockForUpdate()->findOrFail($change->id);
            $subject = $change->subject instanceof Outlet || $change->subject instanceof Business
                ? $change->subject->lockedForUpdate()
                : null;

            if ($subject !== null) {
                $change->setRelation('subject', $subject);
            }

            if (($conflicts = $this->conflicts($change)) !== []) {
                throw ValidationException::withMessages(['change' => $conflicts]);
            }

            /** @var Outlet|Business $subject */
            $lastActivityId = (int) Activity::query()->max('id');

            $this->audit->content(fn () => $this->audit->as('reverted', $reason, function () use ($change, $subject): void {
                $this->revertFields($subject, $this->fieldChanges($change, $subject));
                $this->revertRestore($subject, $change->properties?->get('restore') ?? []);
            }, ['reverts' => ['id' => $change->id, 'event' => $change->event]]));

            $revert = Activity::query()->where('id', '>', $lastActivityId)->where('event', 'reverted')->oldest('id')->first()
                ?? $this->audit->content(fn () => $this->audit->record($subject, 'reverted', $reason, ['reverts' => ['id' => $change->id, 'event' => $change->event]]));

            $change->forceFill([
                'reverted_at' => now(),
                'reverted_by_activity_id' => $revert?->id,
                'reviewed_at' => $change->reviewed_at ?? now(),
                'reviewed_by_id' => $change->reviewed_by_id ?? $admin->id,
            ])->save();

            return $change;
        });
    }

    /**
     * The fields the change set, old and new, limited to what owners can edit.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function fieldChanges(Activity $change, Outlet|Business $subject): array
    {
        $new = $change->attribute_changes?->get('attributes', []) ?? [];
        $old = $change->attribute_changes?->get('old', []) ?? [];
        $fields = [];

        foreach ($new as $field => $value) {
            if ($subject->isFillable($field)) {
                $fields[$field] = ['old' => $old[$field] ?? null, 'new' => $value];
            }
        }

        return $fields;
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     * @return list<string>
     */
    protected function fieldConflicts(Outlet|Business $subject, array $fields): array
    {
        $conflicts = [];

        foreach ($fields as $field => $values) {
            if (! $this->same($subject->getAttribute($field), $values['new'])) {
                $conflicts[] = __(':field was changed again.', ['field' => Str::of($field)->replace('_', ' ')->ucfirst()]);
            }
        }

        return $conflicts;
    }

    /**
     * @param  array<string, mixed>  $restore
     * @return list<string|null>
     */
    protected function restoreConflicts(Outlet|Business $subject, array $restore): array
    {
        $conflicts = [];

        if (array_key_exists('category_id', $restore) && $subject instanceof Outlet
            && $subject->category_id !== $restore['category_id']['new']) {
            $conflicts[] = __('The category was changed again.');
        }

        if (array_key_exists('tag_ids', $restore) && $subject instanceof Outlet
            && $this->sorted($subject->tags()->pluck('tags.id')->all()) !== $this->sorted($restore['tag_ids']['new'])) {
            $conflicts[] = __('The tags were changed again.');
        }

        if (array_key_exists('date_exceptions', $restore) && $subject instanceof Outlet
            && $this->dateExceptions($subject, $restore['date_exceptions']['from']) != $restore['date_exceptions']['new']) {
            $conflicts[] = __('The special dates were changed again.');
        }

        if (array_key_exists('images', $restore)) {
            $conflicts = [...$conflicts, ...$this->imageConflicts($subject, $restore['images'])];
        }

        return $conflicts;
    }

    /**
     * @param  array{old: array<string, array{alt_text: string, position: int, removed: bool}|null>, new: array<string, array{alt_text: string, position: int, removed: bool}|null>}  $images
     * @return list<string>
     */
    protected function imageConflicts(Outlet|Business $subject, array $images): array
    {
        $current = $subject->images()->whereKey(array_keys($images['new']))->get()->keyBy('id');
        $conflicts = [];

        foreach ($images['new'] as $id => $state) {
            $image = $current->get($id);

            if (! $image instanceof Image) {
                $conflicts[] = __('A photo in this change was deleted for good.');

                continue;
            }

            if ($image->toSnapshot() != $state) {
                $conflicts[] = __('A photo in this change was changed again.');
            } elseif (($images['old'][$id]['removed'] ?? true) === false && ! Storage::disk($image->disk)->exists($image->path)) {
                $conflicts[] = __('A removed photo’s file was already deleted.');
            }
        }

        foreach (ImageKind::cases() as $kind) {
            $untouched = $subject->images()->active()->where('kind', $kind)->whereKeyNot(array_keys($images['old']))->count();
            $restored = collect($images['old'])
                ->filter(fn (?array $state, string $id): bool => $state !== null && ! $state['removed'] && $current->get($id)?->kind === $kind)
                ->count();

            if ($untouched + $restored > $kind->limit()) {
                $conflicts[] = __('Reverting would go over the limit of :limit for :kind.', ['limit' => $kind->limit(), 'kind' => Str::lower($kind->label())]);
            }
        }

        return array_values(array_unique($conflicts));
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $fields
     */
    protected function revertFields(Outlet|Business $subject, array $fields): void
    {
        if ($fields === []) {
            return;
        }

        $subject->fill(array_map(fn (array $values): mixed => $values['old'], $fields));

        if ($subject instanceof Outlet && $subject->is_listed && $subject->missingForListing() !== []) {
            $subject->is_listed = false;
        }

        $subject->save();
    }

    /**
     * @param  array<string, mixed>  $restore
     */
    protected function revertRestore(Outlet|Business $subject, array $restore): void
    {
        if ($subject instanceof Outlet && array_key_exists('category_id', $restore)) {
            $this->revertCategory($subject, $restore['category_id']['old']);
        }

        if ($subject instanceof Outlet && array_key_exists('tag_ids', $restore)) {
            $this->syncOutletTags->handle($subject, $restore['tag_ids']['old'], 'reverted');
        }

        if ($subject instanceof Outlet && array_key_exists('date_exceptions', $restore)) {
            $this->revertDateExceptions($subject, $restore['date_exceptions']);
        }

        if (array_key_exists('images', $restore)) {
            $this->manageImages->logged($subject, 'reverted', fn () => $this->revertImages($subject, $restore['images']['old']));
        }
    }

    protected function revertCategory(Outlet $outlet, ?string $categoryId): void
    {
        $previousCategoryId = $outlet->category_id;
        $outlet->category_id = $categoryId;

        if ($outlet->is_listed && $outlet->missingForListing() !== []) {
            $outlet->is_listed = false;
        }

        $outlet->save();

        $this->audit->record($outlet, 'reverted', null, [
            'category' => [
                'old' => $previousCategoryId ? Category::query()->whereKey($previousCategoryId)->value('name') : null,
                'new' => $categoryId ? Category::query()->whereKey($categoryId)->value('name') : null,
            ],
            'restore' => ['category_id' => ['old' => $previousCategoryId, 'new' => $categoryId]],
        ]);
    }

    /**
     * @param  array{from: string, old: list<array<string, mixed>>, new: list<array<string, mixed>>}  $dateExceptions
     */
    protected function revertDateExceptions(Outlet $outlet, array $dateExceptions): void
    {
        $upcoming = fn () => $outlet->dateExceptions()->where('date', '>=', $dateExceptions['from'])->orderBy('date')->get();
        $previous = $upcoming();

        $outlet->dateExceptions()->where('date', '>=', $dateExceptions['from'])->delete();
        $outlet->dateExceptions()->createMany($dateExceptions['old']);

        $current = $upcoming();

        $this->audit->record($outlet, 'reverted', null, [
            'date_exceptions' => [
                'old' => $previous->map(fn (OutletDateException $exception): string => $exception->describe())->values()->all(),
                'new' => $current->map(fn (OutletDateException $exception): string => $exception->describe())->values()->all(),
            ],
            'restore' => ['date_exceptions' => [
                'from' => $dateExceptions['from'],
                'old' => $previous->map->toSnapshot()->values()->all(),
                'new' => $current->map->toSnapshot()->values()->all(),
            ]],
        ]);
    }

    /**
     * Put each image back as it was. An image the change added is removed again.
     *
     * @param  array<string, array{alt_text: string, position: int, removed: bool}|null>  $states
     */
    protected function revertImages(Outlet|Business $subject, array $states): void
    {
        $images = $subject->images()->whereKey(array_keys($states))->get();

        foreach ($images as $image) {
            $state = $states[$image->id];

            $image->forceFill($state === null ? ['removed_at' => $image->removed_at ?? now()] : [
                'alt_text' => $state['alt_text'],
                'position' => $state['position'],
                'removed_at' => $state['removed'] ? ($image->removed_at ?? now()) : null,
            ])->save();
        }
    }

    /**
     * The outlet's special dates from a day on, as the change log keeps them.
     *
     * @return list<array<string, mixed>>
     */
    protected function dateExceptions(Outlet $outlet, string $from): array
    {
        return array_values($outlet->dateExceptions()->where('date', '>=', $from)->orderBy('date')->get()
            ->map(fn (OutletDateException $exception): array => $exception->toSnapshot())
            ->all());
    }

    /**
     * Whether a current value still matches the logged one. Numbers, lists and empty values compare loosely,
     * because the log keeps them as JSON.
     */
    protected function same(mixed $current, mixed $logged): bool
    {
        if (blank($current) && blank($logged)) {
            return true;
        }

        if (is_numeric($current) && is_numeric($logged)) {
            return (float) $current === (float) $logged;
        }

        if (is_array($current) || is_array($logged)) {
            return $current == $logged;
        }

        return $current === $logged;
    }

    /**
     * @param  array<int, string>  $ids
     * @return list<string>
     */
    protected function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }
}
