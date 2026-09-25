<?php

use App\Actions\Vouchers\ClaimVoucher;
use App\Enums\IntegrationCapability;
use App\Enums\VoucherStatus;
use App\Models\Integration;
use App\Models\Outlet;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\Vouchers\VoucherCode;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->integration = Integration::factory()->create();
    Sanctum::actingAs($this->integration);
    $this->outlet = Outlet::factory()->publiclyVisible()->create();
    $this->offer = VoucherOffer::factory()->for($this->outlet->business)->amount('5.00')->create(['status' => 'active']);
    $this->offer->outlets()->attach($this->outlet);
});

/**
 * @return array<string, mixed>
 */
function claimInput(array $overrides = []): array
{
    return ['guest_ref' => 'guest-42', 'idempotency_key' => 'claim-1', ...$overrides];
}

test('an outlet lists the offers a guest can claim there', function () {
    $sponsor = VoucherOffer::factory()->active()->create();
    $sponsor->outlets()->attach($this->outlet);
    VoucherOffer::factory()->for($this->outlet->business)->paused()->create()->outlets()->attach($this->outlet);
    $full = VoucherOffer::factory()->for($this->outlet->business)->create(['status' => 'active', 'voucher_limit' => 1]);
    $full->forceFill(['issued_count' => 1])->save();
    $full->outlets()->attach($this->outlet);

    $response = $this->getJson(route('api.v1.outlets.voucher-offers.index', $this->outlet->slug))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $mine = collect($response->json('data'))->firstWhere('id', $this->offer->id);
    expect($mine)->toMatchArray([
        'discount_type' => 'fixed',
        'discount_value' => '5.00',
        'currency' => 'MYR',
        'sponsor' => ['slug' => $this->outlet->business->slug, 'name' => $this->outlet->business->name],
        'outlets' => [['slug' => $this->outlet->slug, 'name' => $this->outlet->name]],
    ]);
});

test('offers of an outlet that is not public are not found', function () {
    $outlet = Outlet::factory()->create();

    $this->getJson(route('api.v1.outlets.voucher-offers.index', $outlet->slug))->assertNotFound();
});

test('reading offers needs the vouchers read capability', function () {
    Sanctum::actingAs(Integration::factory()->withCapabilities(IntegrationCapability::PlacesRead)->create());

    $this->getJson(route('api.v1.outlets.voucher-offers.index', $this->outlet->slug))->assertForbidden();
});

test('a claim issues a voucher with its code once and stores the guest only as a hash', function () {
    $response = $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput(['outlet' => $this->outlet->slug]))
        ->assertCreated()
        ->assertJsonPath('data.claim_status', 'created')
        ->assertJsonPath('data.voucher.status', 'active')
        ->assertJsonPath('data.voucher.uses_left', 1);

    $voucher = Voucher::sole();
    $code = str_replace('-', '', $response->json('data.code'));
    expect($voucher->code_hash)->toBe(VoucherCode::hash($code))
        ->and($response->json('data.qr_value'))->toBe('V1:'.$code)
        ->and($voucher->integration_id)->toBe($this->integration->id)
        ->and($voucher->guest_ref_hmac)->toBe(ClaimVoucher::guestRefHmac($this->integration, 'guest-42'))
        ->and($voucher->guest_ref_hmac)->not->toContain('guest-42')
        ->and($voucher->outlet_id)->toBe($this->outlet->id);
});

test('resending a claim returns the same voucher and code', function () {
    $first = $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertCreated();

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())
        ->assertOk()
        ->assertJsonPath('data.claim_status', 'idempotent_replay')
        ->assertJsonPath('data.voucher.id', $first->json('data.voucher.id'))
        ->assertJsonPath('data.code', $first->json('data.code'));

    expect(Voucher::count())->toBe(1)
        ->and($this->offer->refresh()->issued_count)->toBe(1);
});

test('reusing an idempotency key for another claim is a conflict', function () {
    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertCreated();

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput(['guest_ref' => 'guest-43']))
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'idempotency_conflict');
});

test('a guest with a live voucher gets it back without the code', function () {
    $first = $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertCreated();

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput(['idempotency_key' => 'claim-2']))
        ->assertOk()
        ->assertJsonPath('data.claim_status', 'existing_active')
        ->assertJsonPath('data.voucher.id', $first->json('data.voucher.id'))
        ->assertJsonPath('data.code', null);
});

test('a guest whose voucher is used can claim another', function () {
    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertCreated();
    Voucher::sole()->forceFill(['status' => VoucherStatus::Used, 'redemption_count' => 1])->save();

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput(['idempotency_key' => 'claim-2']))
        ->assertCreated();
});

test('a fully claimed offer is a conflict', function () {
    $this->offer->update(['voucher_limit' => 1]);
    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertCreated();

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput(['guest_ref' => 'guest-43', 'idempotency_key' => 'claim-2']))
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'offer_limit_reached');
});

test('offers that are not published cannot be claimed', function (string $case) {
    match ($case) {
        'paused' => $this->offer->update(['status' => 'paused']),
        'hidden' => $this->offer->forceFill(['hidden_at' => now(), 'hidden_reason' => 'x'])->save(),
        'no public outlet' => $this->outlet->forceFill(['is_listed' => false])->save(),
        'business suspended' => $this->offer->business->forceFill(['suspended_at' => now()])->save(),
    };

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertNotFound();
})->with(['paused', 'hidden', 'no public outlet', 'business suspended']);

test('the claim outlet must be a public outlet of the offer', function () {
    $other = Outlet::factory()->publiclyVisible()->create();

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput(['outlet' => $other->slug]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.path', '/outlet');
});

test('a partner reads and voids its own voucher', function () {
    $voucher = Voucher::factory()->for($this->offer, 'offer')->claimedBy($this->integration)->create();

    $this->getJson(route('api.v1.vouchers.show', $voucher))
        ->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonMissingPath('data.code');

    $this->postJson(route('api.v1.vouchers.void', $voucher), ['reason_code' => 'guest_cancelled'])
        ->assertOk()
        ->assertJsonPath('data.status', 'void');

    expect($voucher->refresh()->void_reason)->toBe('guest_cancelled');

    $this->postJson(route('api.v1.vouchers.void', $voucher), ['reason_code' => 'guest_cancelled'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'voucher_not_active');
});

test('a partner cannot see or void another partner voucher or a staff voucher', function () {
    $other = Voucher::factory()->for($this->offer, 'offer')->claimedBy(Integration::factory()->create())->create();
    $staff = Voucher::factory()->for($this->offer, 'offer')->create();

    $this->getJson(route('api.v1.vouchers.show', $other))->assertNotFound();
    $this->getJson(route('api.v1.vouchers.show', $staff))->assertNotFound();
    $this->postJson(route('api.v1.vouchers.void', $other), ['reason_code' => 'guest_cancelled'])->assertNotFound();
});

test('a voucher id that is not a uuid is not found', function () {
    $this->getJson('/api/v1/vouchers/not-a-uuid')->assertNotFound()->assertJsonPath('errors.0.code', 'not_found');
});

test('claiming needs the vouchers claim capability', function () {
    Sanctum::actingAs(Integration::factory()->withCapabilities(IntegrationCapability::VouchersRead)->create());

    $this->postJson(route('api.v1.voucher-offers.claims.store', $this->offer), claimInput())->assertForbidden();
});
