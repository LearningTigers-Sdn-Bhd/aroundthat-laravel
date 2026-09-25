<?php

use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    $this->business = Business::factory()->approved()->create();
    $this->outlet = Outlet::factory()->for($this->business)->approved()->create(['name' => 'Bangsar']);
    $this->owner = Membership::factory()->owner()->for($this->business)->create();
});

test('the reports list shows each report with its question', function () {
    $this->actingAs($this->owner->user)
        ->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/reports/index')
            ->where('reports.0.key', 'redemptions')
            ->where('reports.0.question', 'How many vouchers were used, and how much discount did you give?'));
});

test('a report opens on this month by day', function () {
    $offer = VoucherOffer::factory()->for($this->business)->create();
    Redemption::factory()->for(Voucher::factory()->for($offer, 'offer'))->for($this->outlet)->create(['redeemed_at' => now()]);

    $this->actingAs($this->owner->user)
        ->get(route('reports.show', 'redemptions'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/reports/show')
            ->where('report.period', 'this_month')
            ->where('report.period_label', '1–25 Sep 2026')
            ->where('report.grouping', 'day')
            ->has('report.rows', 25)
            ->where('report.tiles.0.value', 1)
            ->where('report.outlet_options.0.label', 'Bangsar'));
});

test('cashiers cannot open reports', function (string $route, array $parameters) {
    $cashier = Membership::factory()->cashier()->for($this->business)->withOutlets($this->outlet)->create();

    $this->actingAs($cashier->user)->get(route($route, $parameters))->assertForbidden();
})->with([
    'the list' => ['reports.index', []],
    'a report' => ['reports.show', ['report' => 'redemptions']],
    'a download' => ['reports.export', ['report' => 'redemptions']],
]);

test('an unknown report returns 404', function () {
    $this->actingAs($this->owner->user)->get(route('reports.show', 'profits'))->assertNotFound();
});

test('an offer or outlet of another business is refused', function (string $field) {
    $other = Outlet::factory()->approved()->create();
    $ids = ['offer' => VoucherOffer::factory()->for($other->business)->create()->id, 'outlet' => $other->id];

    $this->actingAs($this->owner->user)
        ->get(route('reports.show', ['report' => 'redemptions', $field => $ids[$field]]))
        ->assertSessionHasErrors($field);
})->with(['offer', 'outlet']);

test('custom dates that end in the future are refused', function () {
    $this->actingAs($this->owner->user)
        ->get(route('reports.show', ['report' => 'redemptions', 'period' => 'custom', 'from' => '2026-09-01', 'to' => '2026-09-30']))
        ->assertSessionHasErrors(['to' => 'The end date cannot be in the future.']);
});

test('a manager chooses only among the outlets they are assigned to', function () {
    Outlet::factory()->for($this->business)->approved()->create();
    $manager = Membership::factory()->manager()->for($this->business)->withOutlets($this->outlet)->create();

    $this->actingAs($manager->user)
        ->get(route('reports.show', 'redemptions'))
        ->assertInertia(fn (Assert $page) => $page->has('report.outlet_options', 1));
});

test('the download holds the report rows for the chosen dates', function () {
    $offer = VoucherOffer::factory()->for($this->business)->create(['name' => 'Ten off']);
    Redemption::factory()->for(Voucher::factory()->for($offer, 'offer'))->for($this->outlet)
        ->create(['redeemed_at' => CarbonImmutable::parse('2026-09-02 04:00', 'UTC'), 'bill_amount' => '80.00', 'discount_amount' => '8.00']);

    $response = $this->actingAs($this->owner->user)
        ->get(route('reports.export', ['report' => 'redemptions', 'period' => 'custom', 'from' => '2026-09-01', 'to' => '2026-09-03', 'group' => 'offer']));

    $response->assertDownload('redemptions-2026-09-01-to-2026-09-03.csv');
    expect($response->streamedContent())->toBe("Offer,\"Vouchers used\",\"Total bills\",\"Discount given\",\"Customers paid\",Cancelled\n\"Ten off\",1,80.00,8.00,72.00,0\n");
});
