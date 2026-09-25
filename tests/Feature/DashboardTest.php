<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use Carbon\CarbonImmutable;
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
