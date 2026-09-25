<?php

namespace App\Models;

use App\Enums\IntegrationCapability;
use App\Enums\IntegrationType;
use App\Models\Concerns\LocksForUpdate;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An external partner, such as a PMS or a travel agency, that calls the partner API with its own API keys.
 *
 * @property string $id
 * @property string $name
 * @property IntegrationType $type
 * @property list<string> $capabilities
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $suspended_at
 * @property string|null $suspended_by_id
 * @property string|null $suspension_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'type', 'capabilities', 'starts_at', 'expires_at'])]
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasApiTokens, HasFactory, HasUuids, LocksForUpdate, LogsActivity;

    /**
     * Column defaults, so a new integration reads the same before and after it is saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'capabilities' => '[]',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IntegrationType::class,
            'capabilities' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([...$this->getFillable(), 'suspended_at', 'suspension_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The admin who suspended it.
     *
     * @return BelongsTo<User, $this>
     */
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_id');
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Whether its API keys work right now: not suspended, and inside its start and expiry times.
     */
    public function isUsable(?Carbon $at = null): bool
    {
        $at ??= now();

        return ! $this->isSuspended()
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->expires_at === null || $this->expires_at->gt($at));
    }

    public function hasCapability(IntegrationCapability $capability): bool
    {
        return in_array($capability->value, $this->capabilities, true);
    }
}
