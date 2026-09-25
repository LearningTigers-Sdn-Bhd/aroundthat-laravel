<?php

use App\Models\VoucherOffer;
use App\Support\Vouchers\DiscountCalculator;
use App\Support\Vouchers\RedemptionRefused;

function discountOn(VoucherOffer $offer, string $bill, ?string $freeItemValue = null): array
{
    return app(DiscountCalculator::class)->calculate($offer, $bill, $freeItemValue)->toArray();
}

test('a percentage rounds half up to the cent', function () {
    $offer = VoucherOffer::factory()->percentage('15')->make();

    expect(discountOn($offer, '10.10'))->toBe([
        'bill_amount' => '10.10',
        'discount_amount' => '1.52',
        'net_amount' => '8.58',
        'capped' => false,
    ]);
});

test('a percentage stops at the offer cap', function () {
    $offer = VoucherOffer::factory()->percentage('50', '20.00')->make();

    expect(discountOn($offer, '100'))->toMatchArray(['discount_amount' => '20.00', 'net_amount' => '80.00', 'capped' => true]);
});

test('an amount never goes past the bill', function () {
    $offer = VoucherOffer::factory()->amount('15.00')->make();

    expect(discountOn($offer, '40'))->toMatchArray(['discount_amount' => '15.00', 'capped' => false])
        ->and(discountOn($offer, '12.50'))->toMatchArray(['discount_amount' => '12.50', 'net_amount' => '0.00', 'capped' => true]);
});

test('a free item is worth what the cashier enters', function () {
    $offer = VoucherOffer::factory()->freeItem()->make();

    expect(discountOn($offer, '30', '12.9'))->toMatchArray(['discount_amount' => '12.90', 'net_amount' => '17.10']);
});

test('the bill and free item value are checked', function (VoucherOffer $offer, string $bill, ?string $freeItemValue, string $reason) {
    try {
        discountOn($offer, $bill, $freeItemValue);
        $this->fail('The discount was not refused.');
    } catch (RedemptionRefused $refusal) {
        expect($refusal->reason)->toBe($reason);
    }
})->with([
    'below the minimum spend' => fn () => [VoucherOffer::factory()->amount()->make(['min_spend_amount' => '50.00']), '49.99', null, 'below_min_spend'],
    'free item without a value' => fn () => [VoucherOffer::factory()->freeItem()->make(), '30', null, 'free_item_value_required'],
    'free item worth more than the bill' => fn () => [VoucherOffer::factory()->freeItem()->make(), '10', '10.01', 'free_item_value_exceeds_bill'],
]);
