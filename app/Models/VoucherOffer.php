<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\VoucherOfferFactory;
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
 * The terms of one deal a business runs. An offer is not a print run: a voucher is issued when a guest claims it
 * or staff issue one. `status` is the owner's decision and `hidden_at` the admin's; neither overrides the other.
 *
 * Money is a decimal string, never a float.
 *
 * @property string $id
 * @property string $business_id
 * @property string $name
 * @property string|null $description
 * @property DiscountType $discount_type
 * @property string|null $discount_value
 * @property string|null $max_discount_amount
 * @property string|null $min_spend_amount
 * @property string|null $free_item
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int|null $voucher_valid_days
 * @property int|null $voucher_limit
 * @property int $issued_count
 * @property int $uses_per_voucher
 * @property OfferStatus $status
 * @property Carbon|null $hidden_at
 * @property string|null $hidden_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name', 'description', 'discount_type', 'discount_value', 'max_discount_amount', 'min_spend_amount', 'free_item',
    'starts_at', 'ends_at', 'voucher_valid_days', 'voucher_limit', 'uses_per_voucher', 'status',
])]
class VoucherOffer extends Model
{
    /** @use HasFactory<VoucherOfferFactory> */
    use HasFactory, HasUuids, LocksForUpdate, LogsActivity;

    /**
     * After the first voucher is issued, guests hold these terms, so only these fields may still change.
     *
     * @var list<string>
     */
    public const array EDITABLE_AFTER_ISSUE = ['description', 'status', 'ends_at', 'voucher_limit'];

    /**
     * Column defaults, so a new offer reads the same before and after it is saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'issued_count' => 0,
        'uses_per_voucher' => 1,
        'status' => 'draft',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_spend_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'voucher_valid_days' => 'integer',
            'voucher_limit' => 'integer',
            'issued_count' => 'integer',
            'uses_per_voucher' => 'integer',
            'status' => OfferStatus::class,
            'hidden_at' => 'datetime',
        ];
    }

    /**
     * Log every owner- or admin-visible field, old and new. Outlet changes are logged by the actions that make them.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([...$this->getFillable(), 'hidden_at', 'hidden_reason'])
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
     * Where vouchers of this offer can be redeemed. An outlet of another business makes the offer sponsored.
     *
     * @return BelongsToMany<Outlet, $this>
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class)->withPivot('created_at');
    }

    /**
     * Whether vouchers were issued, which freezes the terms guests hold (see EDITABLE_AFTER_ISSUE).
     */
    public function isLocked(): bool
    {
        return $this->issued_count > 0;
    }

    /**
     * Whether an admin took the offer down. The owner cannot run it again until an admin restores it.
     */
    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function hasEnded(?Carbon $at = null): bool
    {
        return $this->ends_at->lessThanOrEqualTo($at ?? now());
    }

    public function hasStarted(?Carbon $at = null): bool
    {
        return $this->starts_at->lessThanOrEqualTo($at ?? now());
    }

    /**
     * Whether the owner runs it and no admin has hidden it. Its dates and its business are separate checks.
     */
    public function isOfferable(): bool
    {
        return $this->status === OfferStatus::Active && ! $this->isHidden();
    }

    /**
     * The one state that matters most right now: hidden, then ended, then not started, then the owner's status.
     *
     * @return 'draft'|'active'|'paused'|'scheduled'|'ended'|'hidden'
     */
    public function state(): string
    {
        return match (true) {
            $this->isHidden() => 'hidden',
            $this->hasEnded() => 'ended',
            $this->status === OfferStatus::Active && ! $this->hasStarted() => 'scheduled',
            default => $this->status->value,
        };
    }

    public function hasReachedLimit(): bool
    {
        return $this->voucher_limit !== null && $this->issued_count >= $this->voucher_limit;
    }

    /**
     * Whether the outlet belongs to another business, so only an admin may attach or detach it.
     */
    public function isSponsoredOutlet(Outlet $outlet): bool
    {
        return $outlet->business_id !== $this->business_id;
    }

    /**
     * Offers a partner may show and a guest may claim now: running, not hidden, inside their dates,
     * and their business approved and not suspended. The voucher limit and the outlet stay with each caller.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $now = now();

        $query->where($this->qualifyColumn('status'), OfferStatus::Active)
            ->whereNull($this->qualifyColumn('hidden_at'))
            ->where($this->qualifyColumn('starts_at'), '<=', $now)
            ->where($this->qualifyColumn('ends_at'), '>', $now)
            ->whereHas('business', fn (Builder $business) => $business->approved()->notSuspended());
    }

    /**
     * Offers that can still issue a voucher.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function underVoucherLimit(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull($this->qualifyColumn('voucher_limit'))
            ->orWhereColumn($this->qualifyColumn('issued_count'), '<', $this->qualifyColumn('voucher_limit')));
    }
}
