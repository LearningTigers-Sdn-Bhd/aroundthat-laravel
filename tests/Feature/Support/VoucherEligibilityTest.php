<?php

use App\Models\Business;
use App\Models\Outlet;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\Vouchers\RedemptionRefused;
use App\Support\Vouchers\VoucherEligibility;

beforeEach(function () {
    $this->business = Business::factory()->approved()->create();
    $this->outlet = Outlet::factory()->for($this->business)->approved()->create();
});

function eligibilityRefusal(string $code, Outlet $outlet): ?string
{
    try {
        app(VoucherEligibility::class)->check($code, $outlet);

        return null;
    } catch (RedemptionRefused $refusal) {
        return $refusal->reason;
    }
}

test('an active voucher of a running offer at one of its outlets is eligible', function () {
    $offer = VoucherOffer::factory()->for($this->business)->create(['status' => 'active']);
    $offer->outlets()->attach($this->outlet);
    $voucher = Voucher::factory()->for($offer, 'offer')->withCode('ABCDE12345')->create();

    expect(app(VoucherEligibility::class)->check('abcde-12345', $this->outlet)->is($voucher))->toBeTrue();
});

test('each problem is refused with its own reason', function (string $case, string $reason) {
    $offer = VoucherOffer::factory()->for($this->business)->create(['status' => 'active', 'uses_per_voucher' => 2]);
    $offer->outlets()->attach($this->outlet);
    $voucher = Voucher::factory()->for($offer, 'offer')->withCode('ABCDE12345')->create();
    $outlet = $this->outlet;
    $code = 'ABCDE12345';

    match ($case) {
        'malformed' => $code = 'ABC',
        'unknown' => $code = 'ZZZZZ99999',
        'void' => $voucher->forceFill(['status' => 'void', 'voided_at' => now(), 'void_reason' => 'x'])->save(),
        'no uses left' => $voucher->forceFill(['redemption_count' => 2])->save(),
        'paused' => $offer->forceFill(['status' => 'paused'])->save(),
        'hidden' => $offer->forceFill(['hidden_at' => now(), 'hidden_reason' => 'x'])->save(),
        'not started' => $offer->forceFill(['starts_at' => now()->addDay(), 'ends_at' => now()->addWeek()])->save(),
        'offer ended' => $offer->forceFill(['starts_at' => now()->subWeek(), 'ends_at' => now()->subMinute()])->save(),
        'voucher expired' => $voucher->forceFill(['expires_at' => now()->subMinute()])->save(),
        'business suspended' => $this->business->forceFill(['suspended_at' => now()])->save(),
        'other outlet' => $outlet = Outlet::factory()->for($this->business)->approved()->create(),
        'outlet suspended' => $outlet->forceFill(['suspended_at' => now()])->save(),
    };

    expect(eligibilityRefusal($code, $outlet))->toBe($reason);
})->with([
    ['malformed', 'invalid_code'],
    ['unknown', 'voucher_not_found'],
    ['void', 'voucher_void'],
    ['no uses left', 'voucher_used'],
    ['paused', 'offer_inactive'],
    ['hidden', 'offer_inactive'],
    ['not started', 'offer_not_started'],
    ['offer ended', 'offer_expired'],
    ['voucher expired', 'offer_expired'],
    ['business suspended', 'owner_suspended'],
    ['other outlet', 'outlet_not_permitted'],
    ['outlet suspended', 'outlet_not_permitted'],
]);

test('a sponsored outlet of another business accepts the voucher', function () {
    $offer = VoucherOffer::factory()->active()->create();
    $offer->outlets()->attach($this->outlet);
    Voucher::factory()->for($offer, 'offer')->withCode('ABCDE12345')->create();

    expect(eligibilityRefusal('ABCDE12345', $this->outlet))->toBeNull();
});
