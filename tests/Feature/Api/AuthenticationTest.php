<?php

use App\Enums\IntegrationCapability;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('the API refuses a request without a usable key', function (Closure $makeKey) {
    $this->withToken($makeKey())
        ->getJson(route('api.v1.categories.index'))
        ->assertUnauthorized()
        ->assertExactJson(['errors' => [['path' => '', 'code' => 'unauthenticated', 'message' => 'A valid API key is required.']]]);
})->with([
    'unknown key' => [fn () => '1|art_not-a-real-key'],
    'suspended integration' => [fn () => Integration::factory()->suspended()->create()->createToken('Server')->plainTextToken],
    'expired integration' => [fn () => Integration::factory()->expired()->create()->createToken('Server')->plainTextToken],
    'integration not started' => [fn () => Integration::factory()->create(['starts_at' => now()->addDay()])->createToken('Server')->plainTextToken],
    'expired key' => [fn () => Integration::factory()->create()->createToken('Server', ['*'], now()->subMinute())->plainTextToken],
]);

test('a logged-in admin session does not authenticate the API', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('api.v1.categories.index'))
        ->assertUnauthorized();
});

test('a usable key authenticates and is marked as used', function () {
    $integration = Integration::factory()->create();
    $key = $integration->createToken('Server');

    $this->withToken($key->plainTextToken)->getJson(route('api.v1.categories.index'))->assertOk();

    expect($key->accessToken->fresh()->last_used_at)->not->toBeNull();
});

test('an integration without the capability is refused', function () {
    $integration = Integration::factory()->withCapabilities(IntegrationCapability::EngagementWrite)->create();

    $this->withToken($integration->createToken('Server')->plainTextToken)
        ->getJson(route('api.v1.categories.index'))
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'forbidden');
});

test('every response carries a request ID, echoing a safe one the partner sent', function () {
    $key = Integration::factory()->create()->createToken('Server')->plainTextToken;

    $this->withToken($key)->withHeader('X-Request-Id', 'partner-req-12345')
        ->getJson(route('api.v1.categories.index'))
        ->assertHeader('X-Request-Id', 'partner-req-12345');

    $generated = $this->withToken($key)->withHeader('X-Request-Id', '<script>')
        ->getJson(route('api.v1.categories.index'))
        ->headers->get('X-Request-Id');

    expect($generated)->toBeUuid();
});

test('an integration is refused after 300 requests a minute', function () {
    $key = Integration::factory()->create()->createToken('Server')->plainTextToken;

    foreach (range(1, 300) as $request) {
        $this->withToken($key)->getJson(route('api.v1.states.index'))->assertOk();
    }

    $this->withToken($key)->getJson(route('api.v1.states.index'))
        ->assertTooManyRequests()
        ->assertJsonPath('errors.0.code', 'rate_limited');
});

test('each integration has its own rate limit', function () {
    $integration = Integration::factory()->create();
    $request = Request::create('/api/v1/states');
    $request->setUserResolver(fn () => $integration);

    expect(RateLimiter::limiter('partner-api')($request)->key)->toBe($integration->id);
});

test('an unknown API path returns the error shape', function () {
    $this->withToken(Integration::factory()->create()->createToken('Server')->plainTextToken)
        ->getJson('/api/v1/nothing-here')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'not_found');
});
