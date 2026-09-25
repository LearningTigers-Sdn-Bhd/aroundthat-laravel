<?php

namespace App\Models;

use App\Enums\EngagementEventType;
use Database\Factories\EngagementEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One traffic event a partner sent about a public outlet. It holds no visitor identity: the partner's session
 * reference is kept only as an HMAC, scoped to the integration. Written in batches by RecordEngagementEvents.
 *
 * @property string $id
 * @property string $integration_id
 * @property string $outlet_id
 * @property EngagementEventType $event_type
 * @property string $anonymous_session_hmac
 * @property string|null $external_event_id
 * @property array<string, string> $metadata
 * @property Carbon $occurred_at
 * @property Carbon $received_at
 * @property string $request_id
 * @property bool $suspect
 */
class EngagementEvent extends Model
{
    /** @use HasFactory<EngagementEventFactory> */
    use HasFactory, HasUuids;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => EngagementEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'suspect' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Integration, $this>
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * @return BelongsTo<Outlet, $this>
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
