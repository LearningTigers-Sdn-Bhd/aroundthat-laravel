<?php

namespace App\Models;

use App\Enums\Ability;
use App\Enums\MembershipRole;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A person's role at one business. Every permission inside a business comes from this row, never from the login.
 *
 * @property string $id
 * @property string $user_id
 * @property string $business_id
 * @property MembershipRole $role
 * @property Carbon|null $suspended_at
 * @property string|null $suspended_by_id
 * @property string|null $suspension_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'role'])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, HasUuids, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'suspended_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['role', 'suspended_at', 'suspension_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Outlets assigned to a manager or cashier. Owners cover every outlet and have none assigned.
     *
     * @return BelongsToMany<Outlet, $this>
     */
    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class)->withPivot('created_at');
    }

    public function isActive(): bool
    {
        return $this->suspended_at === null;
    }

    public function isOwner(): bool
    {
        return $this->role === MembershipRole::Owner;
    }

    /**
     * Whether this active membership allows the ability.
     */
    public function can(Ability $ability): bool
    {
        return $this->isActive() && $this->role->can($ability);
    }

    /**
     * Whether this membership reaches the outlet: owners reach every outlet of their business, others only assigned ones.
     */
    public function canAccessOutlet(Outlet $outlet): bool
    {
        if (! $this->isActive() || $outlet->business_id !== $this->business_id) {
            return false;
        }

        if ($this->role->coversAllOutlets()) {
            return true;
        }

        return $this->outlets()->whereKey($outlet->getKey())->exists();
    }

    /**
     * Outlets this membership reaches, whatever their state.
     *
     * @return HasMany<Outlet, Business>|BelongsToMany<Outlet, $this>
     */
    public function accessibleOutlets(): HasMany|BelongsToMany
    {
        return $this->role->coversAllOutlets() ? $this->business->outlets() : $this->outlets();
    }

    /**
     * Only memberships that are not suspended.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('suspended_at'));
    }

    /**
     * Owners who can still act: the membership and the user are both active.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function usableOwners(Builder $query): void
    {
        $query->active()
            ->where($this->qualifyColumn('role'), MembershipRole::Owner)
            ->whereHas('user', fn (Builder $user) => $user->whereNull('suspended_at'));
    }
}
