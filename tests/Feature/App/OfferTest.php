<?php

use App\Actions\Offers\UpdateOffer;
use App\Data\Forms\OfferFormData;
use App\Enums\DiscountType;
use App\Enums\OfferStatus;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\VoucherOffer;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  list<string>  $outletIds
 */
function ownerOfferInput(array $outletIds = [], array $overrides = []): array
{
    return [
        'name' => '10% off dinner',
        'discount_type' => 'percentage',
        'discount_value' => '10',
        'starts_at' => '2026-10-01T09:00',
        'ends_at' => '2026-12-31T22:00',
        'outlet_ids' => $outletIds,
        ...$overrides,
    ];
}

function offerOwner(): Membership
{
    return Membership::factory()->owner()->for(Business::factory()->approved()->state(['timezone' => 'Asia/Kuala_Lumpur']))->create();
}

test('the offer list shows only the offers of the business being worked in', function () {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->for($owner->business)->create();
    VoucherOffer::factory()->create();

    $this->actingAs($owner->user)
        ->get(route('offers.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/offers/index')
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $offer->id)
            ->where('offers.data.0.state', 'draft')
            ->where('canCreate', true));
});

test('the offer list can be searched and filtered by status', function () {
    $owner = offerOwner();
    $active = VoucherOffer::factory()->for($owner->business)->create(['name' => 'Dinner deal', 'status' => OfferStatus::Active]);
    VoucherOffer::factory()->for($owner->business)->create(['name' => 'Dinner for two', 'status' => OfferStatus::Draft]);
    VoucherOffer::factory()->for($owner->business)->create(['name' => 'Lunch deal', 'status' => OfferStatus::Active]);

    $this->actingAs($owner->user)
        ->get(route('offers.index', ['filter' => ['search' => 'dinner', 'status' => 'active']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('offers.data', 1)
            ->where('offers.data.0.id', $active->id));
});

test('cashiers cannot manage offers', function () {
    $cashier = Membership::factory()->cashier()->create();

    $outlet = Outlet::factory()->for($cashier->business)->create();

    $this->actingAs($cashier->user)->get(route('offers.index'))->assertForbidden();
    $this->post(route('offers.store'), ownerOfferInput([$outlet->id]))->assertForbidden();
});

test('an owner adds a draft offer with dates read in the business time zone', function () {
    $owner = offerOwner();
    $outlet = Outlet::factory()->for($owner->business)->create();

    $response = $this->actingAs($owner->user)->post(route('offers.store'), ownerOfferInput([$outlet->id], ['max_discount_amount' => '20']));

    $offer = $owner->business->voucherOffers()->sole();
    $response->assertRedirect(route('offers.edit', $offer));
    expect($offer->status)->toBe(OfferStatus::Draft)
        ->and($offer->discount_type)->toBe(DiscountType::Percentage)
        ->and($offer->max_discount_amount)->toBe('20.00')
        ->and($offer->starts_at->toIso8601String())->toBe('2026-10-01T01:00:00+00:00')
        ->and($offer->outlets->pluck('id')->all())->toBe([$outlet->id]);
    expect(Activity::forSubject($offer)->forEvent('created')->exists())->toBeTrue();
});

test('an offer drops the discount fields its type does not use', function () {
    $owner = offerOwner();
    $outlet = Outlet::factory()->for($owner->business)->create();

    $this->actingAs($owner->user)->post(route('offers.store'), ownerOfferInput([$outlet->id], [
        'discount_type' => 'free_item',
        'free_item' => 'Iced latte',
        'max_discount_amount' => '20',
    ]))->assertSessionHasNoErrors();

    $offer = $owner->business->voucherOffers()->sole();
    expect($offer->discount_value)->toBeNull()
        ->and($offer->max_discount_amount)->toBeNull()
        ->and($offer->free_item)->toBe('Iced latte');
});

test('each discount type requires its own fields', function (array $input, string $field) {
    $owner = offerOwner();
    $outlet = Outlet::factory()->for($owner->business)->create();

    $this->actingAs($owner->user)
        ->post(route('offers.store'), ownerOfferInput([$outlet->id], $input))
        ->assertSessionHasErrors($field);
})->with([
    'percentage above 100' => [['discount_value' => '101'], 'discount_value'],
    'amount without a value' => [['discount_type' => 'amount', 'discount_value' => null], 'discount_value'],
    'free item without an item' => [['discount_type' => 'free_item', 'discount_value' => null], 'free_item'],
    'more than two decimals' => [['discount_value' => '9.999'], 'discount_value'],
    'ends before it starts' => [['ends_at' => '2026-09-01T09:00'], 'ends_at'],
    'no outlets' => [['outlet_ids' => []], 'outlet_ids'],
]);

test('an offer cannot use an outlet of another business', function () {
    $owner = offerOwner();

    $this->actingAs($owner->user)
        ->post(route('offers.store'), ownerOfferInput([Outlet::factory()->create()->id]))
        ->assertSessionHasErrors('outlet_ids');

    expect(VoucherOffer::count())->toBe(0);
});

test('an owner changes the terms before any voucher is issued', function () {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->for($owner->business)->create();

    $this->actingAs($owner->user)
        ->put(route('offers.update', $offer), ownerOfferInput(overrides: ['discount_type' => 'amount', 'discount_value' => '5']))
        ->assertSessionHasNoErrors();

    expect($offer->refresh()->discount_type)->toBe(DiscountType::Amount)
        ->and($offer->discount_value)->toBe('5.00');
});

test('after vouchers are issued only the description, end date and limit change', function () {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->for($owner->business)->create(['voucher_limit' => 10]);
    $offer->forceFill(['issued_count' => 3])->save();

    $this->actingAs($owner->user)
        ->put(route('offers.update', $offer), [
            'description' => 'Now with dessert',
            'ends_at' => now()->addYear()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d\TH:i'),
            'voucher_limit' => 5,
            'discount_value' => '50',
        ])
        ->assertSessionHasNoErrors();

    expect($offer->refresh())
        ->description->toBe('Now with dessert')
        ->voucher_limit->toBe(5)
        ->discount_value->toBe('10.00');
});

test('after vouchers are issued the limit cannot drop below them and the end cannot move into the past', function (array $input, string $field) {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->for($owner->business)->create();
    $offer->forceFill(['issued_count' => 3])->save();

    $this->actingAs($owner->user)
        ->put(route('offers.update', $offer), [
            'ends_at' => now()->addMonth()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d\TH:i'),
            ...$input,
        ])
        ->assertSessionHasErrors($field);
})->with([
    'limit below issued' => [['voucher_limit' => 2], 'voucher_limit'],
    'end in the past' => [['ends_at' => now()->subDay()->format('Y-m-d\TH:i')], 'ends_at'],
]);

test('the edit lock also holds when the full form is sent after vouchers are issued', function () {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->for($owner->business)->create();

    $action = app(UpdateOffer::class);
    $offer->forceFill(['issued_count' => 1])->save();

    expect(fn () => $action->handle($offer, OfferFormData::from(ownerOfferInput(overrides: ['discount_value' => '50']))))
        ->toThrow(ValidationException::class);
    expect($offer->refresh()->discount_value)->toBe('10.00');
});

test('an owner activates and pauses an offer', function () {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->for($owner->business)->at(Outlet::factory()->for($owner->business)->create())->create();

    $this->actingAs($owner->user)->post(route('offers.activate', $offer))->assertSessionHasNoErrors();
    expect($offer->refresh()->status)->toBe(OfferStatus::Active);

    $this->post(route('offers.pause', $offer))->assertSessionHasNoErrors();
    expect($offer->refresh()->status)->toBe(OfferStatus::Paused);
});

test('an offer cannot be activated while hidden, ended, without outlets or before the business is approved', function (string $case) {
    $owner = offerOwner();
    $outlet = Outlet::factory()->for($owner->business)->create();
    $offer = match ($case) {
        'hidden' => VoucherOffer::factory()->for($owner->business)->hidden()->at($outlet)->create(),
        'ended' => VoucherOffer::factory()->for($owner->business)->ended()->at($outlet)->create(),
        'no outlets' => VoucherOffer::factory()->for($owner->business)->create(),
        'business pending' => VoucherOffer::factory()->for($owner->business)->at($outlet)->create(),
    };

    if ($case === 'business pending') {
        $owner->business->forceFill(['onboarding_status' => 'pending'])->save();
    }

    $this->actingAs($owner->user)->post(route('offers.activate', $offer))->assertSessionHasErrors('offer');

    expect($offer->refresh()->status)->not->toBe(OfferStatus::Active);
})->with(['hidden', 'ended', 'no outlets', 'business pending']);

test('the outlets tab lists the business outlets that are not archived', function () {
    $owner = offerOwner();
    $outlet = Outlet::factory()->for($owner->business)->create();
    Outlet::factory()->for($owner->business)->archived()->create();
    $offer = VoucherOffer::factory()->for($owner->business)->at($outlet)->create();

    $this->actingAs($owner->user)
        ->get(route('offers.outlets.index', $offer))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/offers/outlets')
            ->where('offer.id', $offer->id)
            ->has('outletOptions', 1)
            ->where('outletOptions.0.id', $outlet->id)
            ->where('can.update', true));
});

test('an owner changes the outlets and keeps the sponsored ones', function () {
    $owner = offerOwner();
    [$first, $second] = Outlet::factory()->for($owner->business)->count(2)->create()->all();
    $sponsored = Outlet::factory()->create();
    $offer = VoucherOffer::factory()->for($owner->business)->at($first, $sponsored)->create();

    $this->actingAs($owner->user)
        ->put(route('offers.outlets.update', $offer), ['outlet_ids' => [$second->id]])
        ->assertSessionHasNoErrors();

    expect($offer->outlets()->pluck('outlets.id')->sort()->values()->all())
        ->toBe(collect([$second->id, $sponsored->id])->sort()->values()->all());
    expect(Activity::forSubject($offer)->forEvent('outlets_changed')->sole()->properties['outlets']['new'])
        ->toBe(collect([$second->name, $sponsored->name])->sort()->values()->all());
});

test('an offer of another business returns not found', function () {
    $owner = offerOwner();
    $offer = VoucherOffer::factory()->create();

    $this->actingAs($owner->user)->get(route('offers.edit', $offer))->assertNotFound();
    $this->get(route('offers.outlets.index', $offer))->assertNotFound();
    $this->post(route('offers.activate', $offer))->assertNotFound();
});
