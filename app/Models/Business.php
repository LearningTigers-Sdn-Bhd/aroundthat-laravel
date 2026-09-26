<?php

namespace App\Models;

use App\Enums\OnboardingStatus;
use App\Models\Concerns\HasOnboarding;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A company account: the ownership and authorization boundary.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $registered_name
 * @property string|null $registration_number
 * @property string $contact_email
 * @property string|null $contact_phone
 * @property string|null $address
 * @property string $timezone
 * @property string|null $summary
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'registered_name', 'registration_number', 'contact_email', 'contact_phone', 'address', 'timezone', 'summary', 'description'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasOnboarding, HasSlug, HasUuids, LocksForUpdate, LogsActivity;

    /**
     * Log every owner- or admin-visible field, old and new.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                ...$this->getFillable(),
                'onboarding_status',
                'rejection_reason',
                'suspended_at',
                'suspension_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
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
     * Whether members can change it: not suspended and not waiting for review.
     */
    public function isWritable(): bool
    {
        return ! $this->isSuspended() && $this->onboarding_status !== OnboardingStatus::Pending;
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<Outlet, $this>
     */
    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    /**
     * @return HasMany<VoucherOffer, $this>
     */
    public function voucherOffers(): HasMany
    {
        return $this->hasMany(VoucherOffer::class);
    }
}
