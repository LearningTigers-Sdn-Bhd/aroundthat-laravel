<?php

namespace App\Models;

use App\Enums\VoucherStatus;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One guest's copy of an offer. Staff issue it, or a partner claims it for a guest through the partner API.
 * Look it up by `code_hash`; `code` is encrypted and only decrypted to reveal it again.
 *
 * @property string $id
 * @property string $voucher_offer_id
 * @property string $code_hash
 * @property string $code_prefix
 * @property string $code
 * @property VoucherStatus $status
 * @property int $redemption_count
 * @property Carbon $expires_at
 * @property Carbon|null $voided_at
 * @property string|null $void_reason
 * @property string|null $integration_id
 * @property string|null $guest_ref_hmac
 * @property string|null $claim_key
 * @property string|null $outlet_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory, HasUuids, LocksForUpdate, LogsActivity;

    /**
     * Column defaults, so a new voucher reads the same before and after it is saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'redemption_count' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $hidden = ['code', 'code_hash', 'guest_ref_hmac'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => 'encrypted',
            'status' => VoucherStatus::class,
            'redemption_count' => 'integer',
            'expires_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * Log status changes. Every redemption and cancellation is also a row of its own.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'redemption_count', 'expires_at', 'voided_at', 'void_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<VoucherOffer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(VoucherOffer::class, 'voucher_offer_id');
    }

    /**
     * The partner that claimed it for a guest, if a partner did.
     *
     * @return BelongsTo<Integration, $this>
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * The outlet the partner showed the guest when they claimed it. It never limits where it can be used.
     *
     * @return BelongsTo<Outlet, $this>
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * @return HasMany<Redemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function isExpired(?Carbon $at = null): bool
    {
        return $this->expires_at->lessThanOrEqualTo($at ?? now());
    }

    /**
     * The status with "expired" for an active voucher past its expiry.
     *
     * @return 'active'|'used'|'void'|'expired'
     */
    public function effectiveStatus(?Carbon $at = null): string
    {
        return $this->status === VoucherStatus::Active && $this->isExpired($at) ? 'expired' : $this->status->value;
    }

    /**
     * How many more times it can be used, whatever its status.
     */
    public function usesLeft(): int
    {
        return max(0, $this->offer->uses_per_voucher - $this->redemption_count);
    }
}
