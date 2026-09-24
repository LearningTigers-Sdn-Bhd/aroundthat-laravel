<?php

namespace App\Models;

use App\Enums\TagStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A shared label an outlet can carry, such as Halal or Pet friendly. The slug de-duplicates spellings, so
 * "Halal", "halal" and " HALAL " are one tag. Owners may create tags; an admin approves them before they show publicly.
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property TagStatus $status
 * @property bool $is_active
 * @property string|null $created_by_business_id
 * @property string|null $merged_into_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'is_active'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory, HasSlug, HasUuids, LocksForUpdate, LogsActivity;

    public const int MAX_PER_OUTLET = 10;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TagStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['slug', 'name', 'status', 'is_active', 'merged_into_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsToMany<Outlet, $this>
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class);
    }

    /**
     * The business whose owner typed this tag first. Null for admin-created tags.
     *
     * @return BelongsTo<Business, $this>
     */
    public function createdByBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'created_by_business_id');
    }

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'merged_into_id');
    }

    public function isMerged(): bool
    {
        return $this->merged_into_id !== null;
    }

    /**
     * Tags a business can pick: active approved tags, plus the pending ones it created itself.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function pickableBy(Builder $query, Business $business): void
    {
        $query->where($this->qualifyColumn('is_active'), true)
            ->whereNull($this->qualifyColumn('merged_into_id'))
            ->where(fn (Builder $query) => $query
                ->where($this->qualifyColumn('status'), TagStatus::Approved)
                ->orWhere(fn (Builder $query) => $query
                    ->where($this->qualifyColumn('status'), TagStatus::Pending)
                    ->where($this->qualifyColumn('created_by_business_id'), $business->getKey())));
    }
}
