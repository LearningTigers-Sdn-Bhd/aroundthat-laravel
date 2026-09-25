<?php

use App\Actions\Integrations\ChangeIntegrationStatus;
use App\Models\Activity;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('suspends and reactivates an integration with the reason logged', function () {
    $admin = User::factory()->admin()->create();
    $integration = Integration::factory()->create();

    app(ChangeIntegrationStatus::class)->suspend($admin, $integration, 'Leaked key.');

    expect($integration->refresh()->isUsable())->toBeFalse()
        ->and($integration->suspendedBy->is($admin))->toBeTrue();
    expect(Activity::forSubject($integration)->where('event', 'suspended')->sole()->reason)->toBe('Leaked key.');

    app(ChangeIntegrationStatus::class)->reactivate($integration);

    expect($integration->refresh()->isUsable())->toBeTrue();
});

test('refuses to suspend an integration twice', function () {
    $integration = Integration::factory()->suspended()->create();

    app(ChangeIntegrationStatus::class)->suspend(User::factory()->admin()->create(), $integration, 'Again.');
})->throws(ValidationException::class);

test('an integration is usable only inside its access period', function (array $attributes, bool $usable) {
    expect(Integration::factory()->create($attributes)->isUsable())->toBe($usable);
})->with([
    'no limits' => [[], true],
    'started' => [['starts_at' => now()->subDay()], true],
    'not started yet' => [['starts_at' => now()->addDay()], false],
    'expired' => [['expires_at' => now()->subMinute()], false],
]);
