<?php

namespace App\Actions\Engagement;

use App\Models\EngagementEvent;
use App\Models\Integration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Store a validated batch of a partner's engagement events in one insert. An event whose `external_event_id` the
 * integration already sent, earlier or in the same batch, is skipped.
 */
class RecordEngagementEvents
{
    /**
     * @param  list<array{event_type: string, outlet: string, session_ref: string, external_event_id?: string|null, occurred_at: string, metadata?: array<string, string>}>  $events
     * @param  array<string, string>  $outletIds  Outlet ids keyed by slug.
     * @return array{accepted: int, duplicates: int}
     */
    public function handle(Integration $integration, array $events, array $outletIds, string $requestId): array
    {
        $receivedAt = now();

        $rows = array_map(fn (array $event): array => [
            'id' => (string) Str::uuid7(),
            'integration_id' => $integration->getKey(),
            'outlet_id' => $outletIds[$event['outlet']],
            'event_type' => $event['event_type'],
            'anonymous_session_hmac' => self::sessionHmac($integration, $event['session_ref']),
            'external_event_id' => $event['external_event_id'] ?? null,
            'metadata' => json_encode((object) ($event['metadata'] ?? [])),
            'occurred_at' => Carbon::parse($event['occurred_at'])->utc(),
            'received_at' => $receivedAt,
            'request_id' => $requestId,
        ], $events);

        $accepted = EngagementEvent::query()->insertOrIgnore($rows);

        return ['accepted' => $accepted, 'duplicates' => count($rows) - $accepted];
    }

    /**
     * A one-way hash of the partner's session reference. The integration is part of the input, so the same
     * reference from two partners never matches, and the raw reference is never stored.
     */
    public static function sessionHmac(Integration $integration, string $sessionRef): string
    {
        $key = hash_hmac('sha256', 'engagement-session-hmac-v1', (string) config('app.key'), true);

        return hash_hmac('sha256', "{$integration->getKey()}:{$sessionRef}", $key);
    }
}
