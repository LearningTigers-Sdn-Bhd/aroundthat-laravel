<?php

namespace App\Models;

use App\Models\Concerns\HasOnboarding;
use Database\Factories\OutletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A physical place that a business operates.
 *
 * @property string $id
 * @property string $business_id
 * @property string $name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string $city
 * @property string $state
 * @property string $postcode
 * @property string $country_code
 * @property string $timezone
 * @property string|null $host_outlet_id
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postcode', 'country_code', 'timezone'])]
class Outlet extends Model
{
    /** @use HasFactory<OutletFactory> */
    use HasFactory, HasOnboarding, HasUuids, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Log every owner- or admin-visible field, old and new.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                ...$this->getFillable(),
                'host_outlet_id',
                'onboarding_status',
                'rejection_reason',
                'suspended_at',
                'suspension_reason',
                'archived_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * The outlet this one physically sits inside, such as a mall.
     *
     * @return BelongsTo<Outlet, $this>
     */
    public function hostOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'host_outlet_id');
    }

    /**
     * Outlets that physically sit inside this one.
     *
     * @return HasMany<Outlet, $this>
     */
    public function hostedOutlets(): HasMany
    {
        return $this->hasMany(Outlet::class, 'host_outlet_id');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Whether the outlet can trade: approved, not suspended, not archived, and its business approved and not suspended.
     */
    public function isOperational(): bool
    {
        return $this->isApproved()
            && ! $this->isSuspended()
            && ! $this->isArchived()
            && $this->business->isApproved()
            && ! $this->business->isSuspended();
    }

    /**
     * Only outlets that can trade (see isOperational()).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function operational(Builder $query): void
    {
        $query->approved()
            ->notSuspended()
            ->whereNull($this->qualifyColumn('archived_at'))
            ->whereHas('business', fn (Builder $business) => $business->approved()->notSuspended());
    }
}
