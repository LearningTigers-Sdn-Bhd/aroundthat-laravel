<?php

use App\Models\Business;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('members can visit the dashboard', function () {
    $membership = Membership::factory()->create();
    $this->actingAs($membership->user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('owners see this month in numbers, each linked to its report', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));
    $business = Business::factory()->approved()->create();
    $outlet = Outlet::factory()->for($business)->approved()->create();
    $offer = VoucherOffer::factory()->for($business)->create(['status' => 'active', 'name' => 'Ten off']);
    Redemption::factory()->for(Voucher::factory()->for($offer, 'offer'))->for($outlet)
        ->create(['redeemed_at' => now(), 'bill_amount' => '80.00', 'discount_amount' => '8.00']);
    $owner = Membership::factory()->owner()->for($business)->create();

    $this->actingAs($owner->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('month.label', '1–25 Sep 2026')
            ->where('month.tiles', [
                ['label' => 'Vouchers used', 'value' => 1, 'format' => 'count', 'hint' => null, 'report' => 'redemptions'],
                ['label' => 'Discount given', 'value' => '8.00', 'format' => 'money', 'hint' => null, 'report' => 'redemptions'],
                ['label' => 'Customers paid', 'value' => '72.00', 'format' => 'money', 'hint' => null, 'report' => 'redemptions'],
                ['label' => 'Best offer', 'value' => 'Ten off', 'format' => 'text', 'hint' => '1 voucher used', 'report' => 'offers'],
            ]));
});

test('managers see this month and cashiers a way to the counter', function (string $role, bool $seesNumbers) {
    $business = Business::factory()->approved()->create();
    $membership = Membership::factory()->{$role}()->for($business)->withOutlets(Outlet::factory()->for($business)->approved()->create())->create();

    $this->actingAs($membership->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $seesNumbers ? $page->has('month.tiles', 4) : $page->where('month', null));
})->with([
    'manager' => ['manager', true],
    'cashier' => ['cashier', false],
]);

test('quick actions follow what each role may do', function (string $role, array $actions) {
    $business = Business::factory()->approved()->create();
    $membership = Membership::factory()->{$role}()->for($business)->withOutlets(Outlet::factory()->for($business)->approved()->create())->create();

    $this->actingAs($membership->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('quickActions', $actions));
})->with([
    'owner' => ['owner', ['counter', 'new_offer', 'new_outlet', 'invite_staff', 'export_redemptions']],
    'manager' => ['manager', ['counter', 'new_offer', 'export_redemptions']],
    'cashier' => ['cashier', ['counter']],
]);

test('managers see the latest vouchers used at their outlets only, cancelled ones marked', function () {
    $business = Business::factory()->approved()->create();
    $theirs = Outlet::factory()->for($business)->approved()->create(['name' => 'Harbour']);
    $other = Outlet::factory()->for($business)->approved()->create();
    $offer = VoucherOffer::factory()->for($business)->create(['status' => 'active', 'name' => 'Ten off']);
    $voucher = Voucher::factory()->for($offer, 'offer');
    Redemption::factory()->for($voucher)->for($theirs)->create(['redeemed_at' => now()->subHour(), 'bill_amount' => '50.00', 'discount_amount' => '5.00']);
    $cancelled = Redemption::factory()->for($voucher)->for($theirs)->cancelled()->create(['redeemed_at' => now()->subMinute()]);
    Redemption::factory()->for($voucher)->for($other)->create(['redeemed_at' => now()]);
    $manager = Membership::factory()->manager()->for($business)->withOutlets($theirs)->create();

    $this->actingAs($manager->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('recentRedemptions')
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('recentRedemptions', 2)
                ->where('recentRedemptions.0.id', $cancelled->id)
                ->where('recentRedemptions.0.is_cancelled', true)
                ->where('recentRedemptions.1.offer_name', 'Ten off')
                ->where('recentRedemptions.1.outlet_name', 'Harbour')
                ->where('recentRedemptions.1.bill_amount', '50.00')
                ->where('recentRedemptions.1.is_cancelled', false)));
});

test('owners also see their offers used at other businesses\' outlets', function () {
    $business = Business::factory()->approved()->create();
    $offer = VoucherOffer::factory()->for($business)->create(['status' => 'active']);
    $redemption = Redemption::factory()->for(Voucher::factory()->for($offer, 'offer'))->for(Outlet::factory()->approved())->create();
    Redemption::factory()->create();
    $owner = Membership::factory()->owner()->for($business)->create();

    $this->actingAs($owner->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->has('recentRedemptions', 1)
                ->where('recentRedemptions.0.id', $redemption->id)));
});

test('offers that have not ended are listed, the soonest to end first', function () {
    $business = Business::factory()->approved()->create();
    $later = VoucherOffer::factory()->for($business)->create(['status' => 'active', 'ends_at' => now()->addMonth(), 'voucher_limit' => 100, 'issued_count' => 12]);
    $soon = VoucherOffer::factory()->for($business)->create(['status' => 'active', 'ends_at' => now()->addDays(3)]);
    $paused = VoucherOffer::factory()->for($business)->paused()->create(['ends_at' => now()->addWeeks(2)]);
    $scheduled = VoucherOffer::factory()->for($business)->create(['status' => 'active', 'starts_at' => now()->addDay(), 'ends_at' => now()->addWeeks(3)]);
    VoucherOffer::factory()->for($business)->create();
    VoucherOffer::factory()->for($business)->ended()->create(['status' => 'active']);
    VoucherOffer::factory()->for($business)->hidden()->create(['status' => 'active']);
    VoucherOffer::factory()->create(['status' => 'active']);
    $manager = Membership::factory()->manager()->for($business)->create();

    $this->actingAs($manager->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(fn (Assert $reload) => $reload
                ->where('offers', fn (Collection $offers) => $offers->pluck('id')->all() === [$soon->id, $paused->id, $scheduled->id, $later->id])
                ->where('offers', fn (Collection $offers) => $offers->pluck('state')->all() === ['active', 'paused', 'scheduled', 'active'])
                ->where('offers', fn (Collection $offers) => $offers->pluck('ends_soon')->all() === [true, false, false, false])
                ->where('offers.3.issued_count', 12)
                ->where('offers.3.voucher_limit', 100)));
});

test('cashiers see neither the latest vouchers used nor the offers', function () {
    $business = Business::factory()->approved()->create();
    $cashier = Membership::factory()->cashier()->for($business)->withOutlets(Outlet::factory()->for($business)->approved()->create())->create();

    $this->actingAs($cashier->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('recentRedemptions', null)
            ->where('offers', null));
});

test('cashiers see how many vouchers were used today at their outlets', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));
    $business = Business::factory()->approved()->create(['timezone' => 'Asia/Kuala_Lumpur']);
    $theirs = Outlet::factory()->for($business)->approved()->create();
    $voucher = Voucher::factory()->for(VoucherOffer::factory()->for($business)->create(['status' => 'active']), 'offer');
    Redemption::factory()->for($voucher)->for($theirs)->create(['redeemed_at' => '2026-09-24 16:30:00']);
    Redemption::factory()->for($voucher)->for($theirs)->create(['redeemed_at' => '2026-09-24 15:30:00']);
    Redemption::factory()->for($voucher)->for($theirs)->cancelled()->create(['redeemed_at' => now()]);
    Redemption::factory()->for($voucher)->for(Outlet::factory()->for($business)->approved())->create(['redeemed_at' => now()]);
    $cashier = Membership::factory()->cashier()->for($business)->withOutlets($theirs)->create();

    $this->actingAs($cashier->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('today', ['used' => 1]));
});

test('owners are told what is left to set up', function () {
    $business = Business::factory()->create(['name' => 'Kopi']);
    Outlet::factory()->for($business)->create(['name' => 'A Draft']);
    Outlet::factory()->for($business)->rejected()->create(['name' => 'B Rejected', 'rejection_reason' => 'Blurry photos.']);
    Outlet::factory()->for($business)->approved()->create(['name' => 'C Bare']);
    Outlet::factory()->for($business)->approved()->listed()->create(['name' => 'D Ready', 'is_listed' => false]);
    Outlet::factory()->for($business)->approved()->listed()->create(['name' => 'E Live']);
    Outlet::factory()->for($business)->pending()->create(['name' => 'F Waiting']);
    Outlet::factory()->for($business)->archived()->create(['name' => 'G Archived']);
    VoucherOffer::factory()->for($business)->count(2)->create();
    Invitation::factory()->for($business)->create();
    $owner = Membership::factory()->owner()->for($business)->create();

    $this->actingAs($owner->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('attention', fn (Collection $attention) => $attention->pluck('title')->all() === [
                'Submit your business for review',
                'A Draft is a draft',
                'B Rejected was not approved',
                'C Bare is missing details',
                'D Ready is not listed',
                '2 draft offers',
                '1 invitation not answered',
            ])
            ->where('attention.2.description', 'Blurry photos.')
            ->where('attention.3.description', 'Add summary, category, map location, opening hours to list it for guests.')
            ->where('attention.0.url', route('business.edit')));
});

test('managers are only told about draft offers', function () {
    $business = Business::factory()->create();
    $outlet = Outlet::factory()->for($business)->create();
    VoucherOffer::factory()->for($business)->create();
    Invitation::factory()->for($business)->create();
    $manager = Membership::factory()->manager()->for($business)->withOutlets($outlet)->create();

    $this->actingAs($manager->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('attention', fn (Collection $attention) => $attention->pluck('title')->all() === ['1 draft offer']));
});

test('nothing needs attention once the business is set up', function () {
    $business = Business::factory()->approved()->create();
    Outlet::factory()->for($business)->approved()->listed()->create();
    VoucherOffer::factory()->for($business)->create(['status' => 'active']);
    $owner = Membership::factory()->owner()->for($business)->create();

    $this->actingAs($owner->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('attention', []));
});
