<?php

use App\Models\Activity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('non-admins cannot manage integrations', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.integrations.index'))
        ->assertForbidden();
});

test('an admin adds an integration with its capabilities and Malaysian access dates', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.integrations.store'), [
        'name' => 'Borneo PMS',
        'type' => 'pms',
        'capabilities' => ['places:read'],
        'starts_on' => '2026-10-01',
        'ends_on' => '2027-10-01',
    ]);

    $integration = Integration::query()->sole();
    $response->assertRedirect(route('admin.integrations.keys.index', $integration));

    expect($integration->capabilities)->toBe(['places:read'])
        ->and($integration->starts_at->equalTo(Carbon::parse('2026-10-01 00:00', 'Asia/Kuala_Lumpur')))->toBeTrue()
        ->and($integration->expires_at->equalTo(Carbon::parse('2027-10-01 00:00', 'Asia/Kuala_Lumpur')))->toBeTrue();
});

test('an integration refuses unknown capabilities and an end before its start', function () {
    $this->actingAs($this->admin)->post(route('admin.integrations.store'), [
        'name' => 'Borneo PMS',
        'type' => 'pms',
        'capabilities' => ['vouchers:write'],
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-09-01',
    ])->assertSessionHasErrors(['capabilities.0', 'ends_on']);

    expect(Integration::query()->count())->toBe(0);
});

test('a new API key is shown once and only its hash is stored', function () {
    $integration = Integration::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.integrations.keys.store', $integration), ['name' => 'Production server'])
        ->assertSessionHasNoErrors();

    $response->assertInertiaFlash('api_key.name', 'Production server');
    $key = session('inertia.flash_data.api_key.key');
    $token = $integration->tokens()->sole();

    expect($key)->toBeString()->toStartWith("{$token->id}|art_")
        ->and($token->token)->toBe(hash('sha256', explode('|', $key, 2)[1]))
        ->and(PersonalAccessToken::findToken($key)?->is($token))->toBeTrue();
    expect(Activity::forSubject($integration)->where('event', 'key_created')->sole()->properties['key'])->toBe('Production server');
});

test('rotating a key replaces it and revoking deletes it', function () {
    $integration = Integration::factory()->create();
    $old = $integration->createToken('Production server', ['*'], now()->addYear())->accessToken;

    $this->actingAs($this->admin)
        ->post(route('admin.integrations.keys.rotate', [$integration, $old->id]))
        ->assertSessionHasNoErrors();

    $new = $integration->tokens()->sole();
    expect($new->id)->not->toBe($old->id)
        ->and($new->name)->toBe('Production server')
        ->and($new->expires_at->equalTo($old->expires_at))->toBeTrue();

    $this->delete(route('admin.integrations.keys.destroy', [$integration, $new->id]))->assertSessionHasNoErrors();

    expect($integration->tokens()->count())->toBe(0);
});

test('a key cannot be changed through another integration', function () {
    $integration = Integration::factory()->create();
    $otherKey = Integration::factory()->create()->createToken('Other')->accessToken;

    $this->actingAs($this->admin)
        ->delete(route('admin.integrations.keys.destroy', [$integration, $otherKey->id]))
        ->assertNotFound();

    expect($otherKey->fresh())->not->toBeNull();
});

test('the API keys tab lists its keys without the key itself', function () {
    $integration = Integration::factory()->create();
    $integration->createToken('Production server');

    $this->actingAs($this->admin)
        ->get(route('admin.integrations.keys.index', $integration))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/integrations/keys')
            ->where('integration.keys_count', 1)
            ->where('keys.0.name', 'Production server')
            ->missing('keys.0.token'));
});

test('the activity tab loads only the integration\'s own history', function () {
    $integration = Integration::factory()->create();
    $integration->update(['name' => 'Renamed Integration']);
    Integration::factory()->create()->update(['name' => 'Someone else']);

    $this->actingAs($this->admin)
        ->get(route('admin.integrations.activity.index', $integration))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/integrations/activity')
            ->where('integration.id', $integration->id)
            ->missing('activities')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('activities.0.event', 'updated')
                ->where('activities', fn ($activities) => collect($activities)->every(
                    fn (array $activity) => $activity['subject_id'] === $integration->id,
                ))));
});

test('only admins can open the integration tabs', function (string $route) {
    $integration = Integration::factory()->create();

    $this->actingAs(User::factory()->create())->get(route($route, $integration))->assertForbidden();
})->with(['admin.integrations.show', 'admin.integrations.keys.index', 'admin.integrations.activity.index']);

test('suspending an integration needs a reason', function () {
    $integration = Integration::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.integrations.suspend', $integration))
        ->assertSessionHasErrors('reason');
});
