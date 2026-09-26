<?php

namespace App\Models;

use App\Models\Concerns\LocksForUpdate;
use App\Support\Reports\ReportScope;
use Database\Factories\RedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One use of a voucher at an outlet, with the bill and the discount given. A cancelled redemption keeps its row
 * and gives the use back to the voucher.
 *
 * @property string $id
 * @property string $voucher_id
 * @property string $outlet_id
 * @property string|null $user_id
 * @property numeric-string $bill_amount
 * @property numeric-string $discount_amount
 * @property bool $capped
 * @property string $idempotency_key
 * @property Carbon $redeemed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancelled_by_id
 * @property string|null $cancel_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Redemption extends Model
{
    /** @use HasFactory<RedemptionFactory> */
    use HasFactory, HasUuids, LocksForUpdate;

    /** How long after a redemption the counter can still cancel it. */
    public const int CANCEL_WINDOW_HOURS = 24;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'capped' => 'boolean',
            'redeemed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Voucher, $this>
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return BelongsTo<Outlet, $this>
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * The staff member who redeemed it at the counter.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    public function netAmount(): string
    {
        return bcsub($this->bill_amount, $this->discount_amount, 2);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Whether the counter can still cancel it: not cancelled and inside the cancel window.
     */
    public function isCancellable(): bool
    {
        return ! $this->isCancelled() && $this->redeemed_at->greaterThan(now()->subHours(self::CANCEL_WINDOW_HOURS));
    }

    /**
     * Only redemptions the member's reports cover: at their outlets and, for owners, the business's own offers
     * used at other businesses' outlets.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function coveredBy(Builder $query, ReportScope $scope): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereIn($this->qualifyColumn('outlet_id'), $scope->outletIds)
            ->when($scope->includesOffersElsewhere, fn (Builder $query) => $query
                ->orWhereHas('voucher.offer', fn (Builder $offer) => $offer->where('business_id', $scope->business->id))));
    }
}
