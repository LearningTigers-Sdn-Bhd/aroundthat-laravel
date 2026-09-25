<?php

use App\Actions\Engagement\RecordEngagementEvents;
use App\Enums\IntegrationCapability;
use App\Models\EngagementEvent;
use App\Models\Integration;
use App\Models\Outlet;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->integration = Integration::factory()->create();
    Sanctum::actingAs($this->integration);
    $this->outlet = Outlet::factory()->publiclyVisible()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function engagementEvent(Outlet $outlet, array $overrides = []): array
{
    return [
        'event_type' => 'place_view',
        'outlet' => $outlet->slug,
        'session_ref' => 'session-abc',
        'occurred_at' => '2026-09-25T09:30:00+08:00',
        ...$overrides,
    ];
}

test('a batch of events is stored with a hashed session and the request ID', function () {
    $this->withHeader('X-Request-Id', 'partner-req-12345')
        ->postJson(route('api.v1.engagement-events.store'), ['events' => [
            engagementEvent($this->outlet, ['external_event_id' => 'ev-1']),
            engagementEvent($this->outlet, ['event_type' => 'outbound_click', 'metadata' => ['destination_type' => 'whatsapp']]),
        ]])
        ->assertAccepted()
        ->assertExactJson(['data' => ['accepted' => 2, 'duplicates' => 0]]);

    $view = EngagementEvent::query()->where('external_event_id', 'ev-1')->sole();
    expect($view->outlet_id)->toBe($this->outlet->id)
        ->and($view->integration_id)->toBe($this->integration->id)
        ->and($view->occurred_at->equalTo('2026-09-25T01:30:00Z'))->toBeTrue()
        ->and($view->anonymous_session_hmac)->toBe(RecordEngagementEvents::sessionHmac($this->integration, 'session-abc'))
        ->and($view->anonymous_session_hmac)->not->toContain('session-abc')
        ->and($view->request_id)->toBe('partner-req-12345')
        ->and($view->suspect)->toBeFalse();
    expect(EngagementEvent::query()->where('event_type', 'outbound_click')->sole()->metadata)->toBe(['destination_type' => 'whatsapp']);
});

test('the same session from two integrations never matches', function () {
    expect(RecordEngagementEvents::sessionHmac($this->integration, 'session-abc'))
        ->not->toBe(RecordEngagementEvents::sessionHmac(Integration::factory()->create(), 'session-abc'));
});

test('an event ID already sent is skipped as a duplicate', function () {
    $batch = ['events' => [engagementEvent($this->outlet, ['external_event_id' => 'ev-1']), engagementEvent($this->outlet, ['external_event_id' => 'ev-1'])]];

    $this->postJson(route('api.v1.engagement-events.store'), $batch)
        ->assertExactJson(['data' => ['accepted' => 1, 'duplicates' => 1]]);
    $this->postJson(route('api.v1.engagement-events.store'), $batch)
        ->assertExactJson(['data' => ['accepted' => 0, 'duplicates' => 2]]);

    expect(EngagementEvent::query()->count())->toBe(1);
});

test('one invalid event rejects the whole batch', function () {
    $hidden = Outlet::factory()->publiclyVisible()->hidden()->create();

    $this->postJson(route('api.v1.engagement-events.store'), ['events' => [
        engagementEvent($this->outlet),
        engagementEvent($hidden),
        engagementEvent($this->outlet, ['occurred_at' => '2026-09-25 09:30']),
        engagementEvent($this->outlet, ['event_type' => 'outbound_click']),
        engagementEvent($this->outlet, ['metadata' => ['destination_type' => 'map']]),
    ]])
        ->assertUnprocessable()
        ->assertJsonFragment(['path' => '/events/1/outlet', 'code' => 'unavailable', 'message' => 'The outlet is not available.'])
        ->assertJsonFragment(['path' => '/events/2/occurred_at', 'code' => 'invalid', 'message' => 'Use an RFC 3339 time with a timezone, such as 2026-09-01T08:30:00+08:00.'])
        ->assertJsonFragment(['path' => '/events/3/metadata', 'code' => 'required'])
        ->assertJsonFragment(['path' => '/events/4/metadata', 'code' => 'invalid', 'message' => 'Only outbound clicks have metadata.']);

    expect(EngagementEvent::query()->count())->toBe(0);
});

test('a batch must hold 1 to 100 events', function (int $count) {
    $this->postJson(route('api.v1.engagement-events.store'), ['events' => array_fill(0, $count, engagementEvent($this->outlet))])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.path', '/events');
})->with([0, 101]);

test('an integration without the capability cannot send events', function () {
    Sanctum::actingAs(Integration::factory()->withCapabilities(IntegrationCapability::PlacesRead)->create());

    $this->postJson(route('api.v1.engagement-events.store'), ['events' => [engagementEvent($this->outlet)]])
        ->assertForbidden();
});
