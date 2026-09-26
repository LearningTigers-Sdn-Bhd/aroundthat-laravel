<?php

use App\Models\Business;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Illuminate\Database\QueryException;

test('published offers are active, not hidden, inside their dates and belong to an approved active business', function () {
    $published = VoucherOffer::factory()->active()->create();
    VoucherOffer::factory()->active()->paused()->create();
    VoucherOffer::factory()->active()->hidden()->create();
    VoucherOffer::factory()->active()->ended()->create();
    VoucherOffer::factory()->active()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addWeek()]);
    VoucherOffer::factory()->active()->for(Business::factory()->approved()->suspended())->create();
    VoucherOffer::factory()->active()->for(Business::factory()->pending())->create();

    expect(VoucherOffer::published()->pluck('id')->all())->toBe([$published->id]);
});

test('offers under their voucher limit exclude full offers', function () {
    $unlimited = VoucherOffer::factory()->create();
    $open = VoucherOffer::factory()->create(['voucher_limit' => 2]);
    $full = VoucherOffer::factory()->create(['voucher_limit' => 2]);
    $full->forceFill(['issued_count' => 2])->save();

    expect(VoucherOffer::underVoucherLimit()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$unlimited->id, $open->id])->sort()->values()->all());
    expect($full->hasReachedLimit())->toBeTrue();
});

test('an outlet of another business is a sponsored outlet', function () {
    $offer = VoucherOffer::factory()->create();
    $own = Outlet::factory()->for($offer->business)->create();
    $other = Outlet::factory()->create();

    expect($offer->isSponsoredOutlet($own))->toBeFalse()
        ->and($offer->isSponsoredOutlet($other))->toBeTrue();
});

test('the database rejects a percentage above 100', function () {
    VoucherOffer::factory()->percentage('100.01')->create();
})->throws(QueryException::class, 'voucher_offers_percentage_check');

test('the database rejects a free item offer without an item', function () {
    VoucherOffer::factory()->freeItem()->create(['free_item' => null]);
})->throws(QueryException::class, 'voucher_offers_free_item_check');

test('the database rejects a cap on an amount offer', function () {
    VoucherOffer::factory()->amount()->create(['max_discount_amount' => '3.00']);
})->throws(QueryException::class, 'voucher_offers_max_discount_check');

test('the database rejects an offer that ends before it starts', function () {
    VoucherOffer::factory()->create(['starts_at' => now(), 'ends_at' => now()->subHour()]);
})->throws(QueryException::class, 'voucher_offers_window_check');

test('the database rejects issuing past the voucher limit', function () {
    $offer = VoucherOffer::factory()->create(['voucher_limit' => 1]);

    $offer->forceFill(['issued_count' => 2])->save();
})->throws(QueryException::class, 'voucher_offers_limit_check');
