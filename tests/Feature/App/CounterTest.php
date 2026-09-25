<?php

use App\Enums\VoucherStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $business = Business::factory()->approved()->create();
    $this->outlet = Outlet::factory()->for($business)->approved()->create();
    $this->cashier = Membership::factory()->cashier()->for($business)->withOutlets($this->outlet)->create();
    $this->offer = VoucherOffer::factory()->for($business)->percentage('10')->create(['status' => 'active', 'uses_per_voucher' => 2]);
    $this->offer->outlets()->attach($this->outlet);
    $this->voucher = Voucher::factory()->for($this->offer, 'offer')->withCode('ABCDE12345')->create();
});

function redeemInput(Outlet $outlet, array $overrides = []): array
{
    return [
        'outlet_id' => $outlet->id,
        'code' => 'ABCDE-12345',
        'bill_amount' => '80.00',
        'idempotency_key' => (string) Str::uuid(),
        ...$overrides,
    ];
}

test('the counter opens at the only outlet the cashier works at', function () {
    Redemption::factory()->for($this->voucher)->for($this->outlet)->create();

    $this->actingAs($this->cashier->user)
        ->get(route('counter.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/counter')
            ->has('outlets', 1)
            ->where('outlet.id', $this->outlet->id)
            ->where('today.total', 1)
            ->where('today.redemptions.0.code_prefix', 'ABCD'));
});

test('checking a code shows the offer and previews the discount without saving', function () {
    $this->actingAs($this->cashier->user)
        ->postJson(route('counter.check'), ['outlet_id' => $this->outlet->id, 'code' => 'abcde12345', 'bill_amount' => '80'])
        ->assertOk()
        ->assertJsonPath('eligible', true)
        ->assertJsonPath('offer.name', $this->offer->name)
        ->assertJsonPath('voucher.uses_left', 2)
        ->assertJsonPath('amounts.discount_amount', '8.00')
        ->assertJsonPath('amounts.net_amount', '72.00');

    expect(Redemption::count())->toBe(0);
});

test('checking a code that cannot be used says why', function () {
    $this->actingAs($this->cashier->user)
        ->postJson(route('counter.check'), ['outlet_id' => $this->outlet->id, 'code' => 'ZZZZZ99999'])
        ->assertOk()
        ->assertJsonPath('eligible', false)
        ->assertJsonPath('reason', 'voucher_not_found');
});

test('redeeming uses the voucher once and marks it used after its last use', function () {
    $this->actingAs($this->cashier->user)
        ->post(route('counter.redeem'), redeemInput($this->outlet))
        ->assertRedirect(route('counter.show', ['outlet' => $this->outlet->id]))
        ->assertInertiaFlash('redemption.discount_amount', '8.00');

    $redemption = Redemption::sole();
    expect($redemption->user_id)->toBe($this->cashier->user_id)
        ->and($redemption->bill_amount)->toBe('80.00')
        ->and($this->voucher->refresh()->redemption_count)->toBe(1)
        ->and($this->voucher->status)->toBe(VoucherStatus::Active);

    $this->post(route('counter.redeem'), redeemInput($this->outlet));

    expect($this->voucher->refresh()->status)->toBe(VoucherStatus::Used);

    $this->post(route('counter.redeem'), redeemInput($this->outlet))->assertSessionHasErrors('code');
    expect(Redemption::count())->toBe(2);
});

test('sending the same idempotency key again returns the first redemption', function () {
    $input = redeemInput($this->outlet);

    $this->actingAs($this->cashier->user)->post(route('counter.redeem'), $input);
    $this->post(route('counter.redeem'), $input)->assertSessionHasNoErrors();

    expect(Redemption::count())->toBe(1)
        ->and($this->voucher->refresh()->redemption_count)->toBe(1);
});

test('a refused redemption is logged on the outlet', function () {
    $this->voucher->forceFill(['status' => 'void', 'voided_at' => now(), 'void_reason' => 'Cancelled.'])->save();

    $this->actingAs($this->cashier->user)
        ->post(route('counter.redeem'), redeemInput($this->outlet))
        ->assertSessionHasErrors(['code' => 'This voucher was cancelled.']);

    $activity = Activity::forSubject($this->outlet)->forEvent('redemption_refused')->sole();
    expect($activity->properties['reason'])->toBe('voucher_void')
        ->and($activity->properties['code_prefix'])->toBe('ABCD');
});

test('a cashier at a sponsored outlet redeems another business voucher', function () {
    $sponsoredOutlet = Outlet::factory()->for(Business::factory()->approved())->approved()->create();
    $cashier = Membership::factory()->cashier()->for($sponsoredOutlet->business)->withOutlets($sponsoredOutlet)->create();
    $this->offer->outlets()->attach($sponsoredOutlet);

    $this->actingAs($cashier->user)
        ->post(route('counter.redeem'), redeemInput($sponsoredOutlet))
        ->assertSessionHasNoErrors();

    expect(Redemption::sole()->outlet_id)->toBe($sponsoredOutlet->id);
});

test('a cashier cannot use the counter at an outlet they do not work at', function () {
    $otherOutlet = Outlet::factory()->for($this->outlet->business)->approved()->create();
    $this->offer->outlets()->attach($otherOutlet);

    $this->actingAs($this->cashier->user)
        ->post(route('counter.redeem'), redeemInput($otherOutlet))
        ->assertNotFound();
    $this->postJson(route('counter.check'), ['outlet_id' => $otherOutlet->id, 'code' => 'ABCDE12345'])->assertNotFound();
});

test('cancelling a redemption gives the use back to the voucher', function () {
    $this->voucher->forceFill(['status' => 'used', 'redemption_count' => 2])->save();
    $redemption = Redemption::factory()->for($this->voucher)->for($this->outlet)->create();

    $this->actingAs($this->cashier->user)
        ->post(route('counter.cancel', $redemption), ['reason' => 'Wrong bill.'])
        ->assertSessionHasNoErrors();

    expect($redemption->refresh()->cancelled_at)->not->toBeNull()
        ->and($redemption->cancelled_by_id)->toBe($this->cashier->user_id)
        ->and($this->voucher->refresh()->redemption_count)->toBe(1)
        ->and($this->voucher->status)->toBe(VoucherStatus::Active);

    $this->post(route('counter.cancel', $redemption), ['reason' => 'Again.'])->assertSessionHasErrors('redemption');
    expect($this->voucher->refresh()->redemption_count)->toBe(1);
});

test('a redemption older than the cancel window cannot be cancelled', function () {
    $redemption = Redemption::factory()->for($this->voucher)->for($this->outlet)->create(['redeemed_at' => now()->subHours(25)]);

    $this->actingAs($this->cashier->user)
        ->post(route('counter.cancel', $redemption), ['reason' => 'Late.'])
        ->assertSessionHasErrors('redemption');
});

test('a redemption at an outlet the cashier does not work at cannot be cancelled', function () {
    $redemption = Redemption::factory()->for($this->voucher)->create();

    $this->actingAs($this->cashier->user)
        ->post(route('counter.cancel', $redemption), ['reason' => 'Wrong bill.'])
        ->assertNotFound();
});
