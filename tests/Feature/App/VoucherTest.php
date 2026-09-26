<?php

use App\Enums\VoucherStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = Membership::factory()->owner()->for(Business::factory()->approved())->create();
    $this->offer = VoucherOffer::factory()->for($this->owner->business)->create(['status' => 'active']);
});

test('the voucher list shows the code prefix and status but never the code', function () {
    $voucher = Voucher::factory()->for($this->offer, 'offer')->create();
    Voucher::factory()->for($this->offer, 'offer')->expired()->create();

    $this->actingAs($this->owner->user)
        ->get(route('offers.vouchers.index', $this->offer))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/offers/vouchers')
            ->has('vouchers.data', 2)
            ->where('vouchers.data.1.id', $voucher->id)
            ->where('vouchers.data.1.code_prefix', $voucher->code_prefix)
            ->where('vouchers.data.1.status', 'active')
            ->where('vouchers.data.0.status', 'expired')
            ->missing('vouchers.data.0.code')
            ->where('can.update', true)
            ->where('can.issue', true)
            ->where('can.void', true));

    $this->get(route('offers.vouchers.index', [$this->offer, 'filter' => ['status' => 'expired']]))
        ->assertInertia(fn (Assert $page) => $page->has('vouchers.data', 1));
});

test('staff issue a voucher and see its code once', function () {
    $response = $this->actingAs($this->owner->user)
        ->from(route('offers.vouchers.index', $this->offer))
        ->post(route('offers.vouchers.store', $this->offer));

    $voucher = $this->offer->vouchers()->sole();
    $response->assertRedirect(route('offers.vouchers.index', $this->offer))
        ->assertInertiaFlash('voucher.id', $voucher->id)
        ->assertInertiaFlash('voucher.qr_value', 'V1:'.$voucher->code);
});

test('revealing a code again needs the password and is logged', function () {
    $voucher = Voucher::factory()->for($this->offer, 'offer')->withCode('ABCDE12345')->create();

    $this->actingAs($this->owner->user)
        ->post(route('offers.vouchers.reveal', [$this->offer, $voucher]), ['password' => 'wrong'])
        ->assertSessionHasErrors('password');
    expect(Activity::forSubject($voucher)->forEvent('code_revealed')->exists())->toBeFalse();

    $this->post(route('offers.vouchers.reveal', [$this->offer, $voucher]), ['password' => 'password'])
        ->assertInertiaFlash('voucher.code', 'ABCDE-12345');
    expect(Activity::forSubject($voucher)->forEvent('code_revealed')->exists())->toBeTrue();
});

test('an owner voids an active voucher with a reason', function () {
    $voucher = Voucher::factory()->for($this->offer, 'offer')->create();

    $this->actingAs($this->owner->user)
        ->post(route('offers.vouchers.void', [$this->offer, $voucher]), ['reason' => 'Guest asked to cancel.'])
        ->assertSessionHasNoErrors();

    expect($voucher->refresh())
        ->status->toBe(VoucherStatus::Void)
        ->void_reason->toBe('Guest asked to cancel.');
});

test('used, void and expired vouchers cannot be voided', function (string $state) {
    $voucher = Voucher::factory()->for($this->offer, 'offer')->{$state}()->create();

    $this->actingAs($this->owner->user)
        ->post(route('offers.vouchers.void', [$this->offer, $voucher]), ['reason' => 'Cancel.'])
        ->assertSessionHasErrors('voucher');
})->with(['used', 'void', 'expired']);

test('managers issue vouchers but only owners void them', function () {
    $manager = Membership::factory()->manager()->for($this->owner->business)->create();
    $voucher = Voucher::factory()->for($this->offer, 'offer')->create();

    $this->actingAs($manager->user)->post(route('offers.vouchers.store', $this->offer))->assertSessionHasNoErrors();
    $this->post(route('offers.vouchers.void', [$this->offer, $voucher]), ['reason' => 'Cancel.'])->assertForbidden();
});

test('a voucher of another offer is not found through this offer', function () {
    $otherOffer = VoucherOffer::factory()->for($this->owner->business)->create();
    $voucher = Voucher::factory()->for($otherOffer, 'offer')->create();

    $this->actingAs($this->owner->user)
        ->post(route('offers.vouchers.void', [$this->offer, $voucher]), ['reason' => 'Cancel.'])
        ->assertNotFound();
});
