<?php

use App\Models\User;

test('only admins can read the API docs', function (Closure $makeUser, int $status) {
    $this->actingAs($makeUser())->get('/docs/api.json')->assertStatus($status);
})->with([
    'admin' => [fn () => User::factory()->admin()->create(), 200],
    'owner' => [fn () => User::factory()->create(), 403],
]);

test('the API docs describe every partner endpoint, bearer authentication and the error shape', function () {
    $spec = $this->actingAs(User::factory()->admin()->create())->getJson('/docs/api.json')->json();

    expect(array_keys($spec['paths']))->toEqualCanonicalizing([
        '/v1/categories', '/v1/tags', '/v1/states', '/v1/outlets', '/v1/outlets/{slug}', '/v1/engagement-events',
        '/v1/outlets/{slug}/voucher-offers', '/v1/voucher-offers/{offer}/claims', '/v1/vouchers/{voucher}', '/v1/vouchers/{voucher}/void',
    ])
        ->and($spec['components']['securitySchemes'])->toHaveKey('http')
        ->and($spec['components']['responses']['ValidationException']['content']['application/json']['schema']['properties'])->toHaveKeys(['errors'])
        ->and(array_column($spec['paths']['/v1/outlets']['get']['parameters'], 'name'))->toContain('filter[category]', 'filter[updated_since]', 'cursor');
});
