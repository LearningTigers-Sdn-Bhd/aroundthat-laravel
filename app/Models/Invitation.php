<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\MembershipRole;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An emailed offer to join a business with a role and, for managers and cashiers, outlets.
 *
 * Only a hash of the link's token is stored. The status is worked out from the timestamps.
 *
 * @property string $id
 * @property string $business_id
 * @property string $email
 * @property MembershipRole $role
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property string|null $invited_by_id
 * @property string|null $accepted_by_id
 * @property Carbon|null $sent_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['email', 'role'])]
#[Hidden(['token_hash'])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory, HasUuids, LocksForUpdate, LogsActivity;

    /**
     * How long a link stays valid after it is issued.
     */
    public const int LIFETIME_DAYS = 14;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['email', 'role', 'accepted_at', 'declined_at', 'cancelled_at'])
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
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_id');
    }

    /**
     * Outlets a manager or cashier will work at. Owner invitations have none.
     *
     * @return BelongsToMany<Outlet, $this>
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class);
    }

    /**
     * Find the invitation a link points to.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token_hash', static::hashToken($token))->first();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Give the invitation a new link and a fresh expiry. Earlier links stop working.
     *
     * @return string The token for the link. It is not stored, so send it now.
     */
    public function issueToken(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'token_hash' => static::hashToken($token),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);

        return $token;
    }

    public function status(): InvitationStatus
    {
        return match (true) {
            $this->accepted_at !== null => InvitationStatus::Accepted,
            $this->declined_at !== null => InvitationStatus::Declined,
            $this->cancelled_at !== null => InvitationStatus::Cancelled,
            $this->expires_at->isPast() => InvitationStatus::Expired,
            default => InvitationStatus::Pending,
        };
    }

    /**
     * Not accepted, declined or cancelled yet, even if expired.
     */
    public function isOpen(): bool
    {
        return $this->accepted_at === null && $this->declined_at === null && $this->cancelled_at === null;
    }

    public function isPending(): bool
    {
        return $this->status() === InvitationStatus::Pending;
    }

    /**
     * Not accepted, declined or cancelled yet, even if expired. At most one per email and business.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull(['accepted_at', 'declined_at', 'cancelled_at']);
    }

    /**
     * Open and not expired: the link can still be used.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->open()->where('expires_at', '>', now());
    }
}
