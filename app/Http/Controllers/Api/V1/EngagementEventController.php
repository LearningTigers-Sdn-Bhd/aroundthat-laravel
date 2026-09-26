<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Engagement\RecordEngagementEvents;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEngagementEventsRequest;
use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

/**
 * @tags Engagement
 */
class EngagementEventController extends Controller
{
    /**
     * Send engagement events
     *
     * A batch of 1 to 100 place impressions, views and outbound clicks. If any event is invalid, none is stored and
     * every error is returned. Events with an `external_event_id` you already sent count as duplicates, so a batch can
     * be resent safely after a timeout.
     */
    public function store(StoreEngagementEventsRequest $request, RecordEngagementEvents $recordEvents): JsonResponse
    {
        $integration = Auth::guard('sanctum')->user();
        abort_unless($integration instanceof Integration, 401);

        /** @var list<array{event_type: string, outlet: string, session_ref: string, external_event_id?: string|null, occurred_at: string, metadata?: array<string, string>}> $events */
        $events = $request->validated('events');

        $result = $recordEvents->handle($integration, $events, $request->publicOutletIds(), (string) Context::get('request_id'));

        return response()->json(['data' => $result], 202);
    }
}
