<?php

namespace App\Models;

use App\Enums\OnboardingStatus;
use App\Models\Concerns\HasOnboarding;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\OutletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A physical place that a business operates.
 *
 * @property string $id
 * @property string $business_id
 * @property string $name
 * @property string $slug
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
 * @property string|null $category_id
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $google_maps_url
 * @property string|null $website
 * @property string|null $whatsapp
 * @property string|null $facebook
 * @property string|null $instagram
 * @property array<int, list<array{opens: string, closes: string}>>|null $regular_hours
 * @property bool $is_listed
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'contact_email', 'contact_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postcode', 'country_code', 'timezone',
    'summary', 'description', 'category_id', 'latitude', 'longitude', 'google_maps_url', 'website', 'whatsapp', 'facebook', 'instagram', 'regular_hours', 'is_listed',
])]
class Outlet extends Model
{
    /** @use HasFactory<OutletFactory> */
    use HasFactory, HasOnboarding, HasSlug, HasUuids, LocksForUpdate, LogsActivity;

    /**
     * Column defaults, so a new outlet reads the same before and after it is saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_listed' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'regular_hours' => 'array',
            'is_listed' => 'boolean',
        ];
    }

    /**
     * Log every owner- or admin-visible field, old and new. The category is logged by name by UpdateOutletPublicProfile.
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
            ->logExcept(['category_id'])
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
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /**
     * Uploaded images, including removed ones. Scope with `active()` for what visitors see.
     *
     * @return MorphMany<Image, $this>
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Dates with different hours, such as public holidays, oldest first.
     *
     * @return HasMany<OutletDateException, $this>
     */
    public function dateExceptions(): HasMany
    {
        return $this->hasMany(OutletDateException::class)->orderBy('date');
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

    /**
     * Whether members can change it: not archived, not suspended, not waiting for review, and its business not suspended.
     */
    public function isWritable(): bool
    {
        return ! $this->isArchived()
            && ! $this->isSuspended()
            && $this->onboarding_status !== OnboardingStatus::Pending
            && ! $this->business->isSuspended();
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
     * The public fields still empty before the owner can list the outlet, as field names.
     *
     * @return list<string>
     */
    public function missingForListing(): array
    {
        return array_values(array_filter([
            blank($this->summary) ? 'summary' : null,
            blank($this->category_id) ? 'category_id' : null,
            $this->latitude === null || $this->longitude === null ? 'coordinates' : null,
            blank($this->regular_hours) ? 'hours' : null,
        ]));
    }

    /**
     * Whether visitors can see the outlet: it can trade, the owner listed it, and its public fields are complete.
     */
    public function isPublic(): bool
    {
        return $this->is_listed && $this->missingForListing() === [] && $this->isOperational();
    }

    /**
     * Only outlets visitors can see (see isPublic()).
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function public(Builder $query): void
    {
        $query->operational()
            ->where($this->qualifyColumn('is_listed'), true)
            ->whereNotNull([
                $this->qualifyColumn('summary'),
                $this->qualifyColumn('category_id'),
                $this->qualifyColumn('latitude'),
                $this->qualifyColumn('longitude'),
                $this->qualifyColumn('regular_hours'),
            ])
            ->where($this->qualifyColumn('summary'), '<>', '');
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
