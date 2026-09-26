<?php

use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportScope;
use App\Support\Reports\Types\OfferPerformanceReport;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    $this->business = Business::factory()->approved()->create();
    $this->bangsar = Outlet::factory()->for($this->business)->approved()->create();
    $this->klcc = Outlet::factory()->for($this->business)->approved()->create();
    $this->owner = Membership::factory()->owner()->for($this->business)->create();
});

function runningOffer(Business $business, array $attributes = []): VoucherOffer
{
    return VoucherOffer::factory()->for($business)->create(['status' => 'active', ...$attributes]);
}

function usedAt(VoucherOffer $offer, Outlet $outlet, string $discount = '10.00', bool $cancelled = false): Redemption
{
    return Redemption::factory()
        ->when($cancelled, fn ($factory) => $factory->cancelled())
        ->for(Voucher::factory()->for($offer, 'offer'))
        ->for($outlet)
        ->create(['discount_amount' => $discount]);
}

function offerFilters(Membership $membership, ?string $outletId = null): ReportFilters
{
    return new ReportFilters(
        scope: ReportScope::for($membership),
        period: ReportPeriod::preset(ReportPeriodPreset::ThisMonth, 'Asia/Kuala_Lumpur'),
        grouping: ReportGrouping::Offer,
        outletId: $outletId,
    );
}

test('each offer shows vouchers given out, from partners, used, discount and what is left', function () {
    $offer = runningOffer($this->business, ['name' => 'Ten off', 'voucher_limit' => 50, 'issued_count' => 4]);
    Voucher::factory()->for($offer, 'offer')->claimedBy()->create();
    usedAt($offer, $this->bangsar, '12.50');
    usedAt($offer, $this->klcc, '7.50');
    usedAt($offer, $this->klcc, '5.00', cancelled: true);

    $rows = app(OfferPerformanceReport::class)->rows(offerFilters($this->owner));

    expect($rows)->toBe([[
        'key' => $offer->id,
        'label' => 'Ten off',
        'status' => 'Active',
        'given' => 4,
        'from_partners' => 1,
        'used' => 2,
        'discount' => '20.00',
        'left' => '46',
        'hidden' => false,
    ]]);
});

test('an offer without a limit has no limit left to give', function () {
    runningOffer($this->business, ['voucher_limit' => null]);

    $rows = app(OfferPerformanceReport::class)->rows(offerFilters($this->owner));

    expect($rows[0]['left'])->toBe('No limit');
});

test('only offers that ran in the period are listed, drafts left out', function () {
    $running = runningOffer($this->business);
    runningOffer($this->business, ['starts_at' => '2026-07-01', 'ends_at' => '2026-08-01']);
    VoucherOffer::factory()->for($this->business)->create(['status' => 'draft']);
    runningOffer(Business::factory()->approved()->create());

    $rows = app(OfferPerformanceReport::class)->rows(offerFilters($this->owner));

    expect(array_column($rows, 'key'))->toBe([$running->id]);
});

test('the outlet choice narrows vouchers used but not vouchers given out', function () {
    $offer = runningOffer($this->business);
    usedAt($offer, $this->bangsar);
    usedAt($offer, $this->klcc);

    $rows = app(OfferPerformanceReport::class)->rows(offerFilters($this->owner, $this->bangsar->id));

    expect($rows[0])->toMatchArray(['given' => 2, 'used' => 1]);
});

test('a manager counts only the vouchers used at their outlets', function () {
    $manager = Membership::factory()->manager()->for($this->business)->withOutlets($this->bangsar)->create();
    $offer = runningOffer($this->business);
    usedAt($offer, $this->bangsar);
    usedAt($offer, $this->klcc);
    usedAt($offer, Outlet::factory()->approved()->create());

    $rows = app(OfferPerformanceReport::class)->rows(offerFilters($manager));

    expect($rows[0]['used'])->toBe(1);
});

test('the best offer is the one with the most vouchers used', function () {
    $quiet = runningOffer($this->business, ['name' => 'Quiet']);
    $busy = runningOffer($this->business, ['name' => 'Busy']);
    usedAt($quiet, $this->bangsar);
    usedAt($busy, $this->bangsar);
    usedAt($busy, $this->klcc);

    $best = app(OfferPerformanceReport::class)->summary(offerFilters($this->owner))[2];

    expect($best->value)->toBe('Busy')
        ->and($best->hint)->toBe('2 vouchers used');
});

test('there is no best offer before any voucher is used', function () {
    runningOffer($this->business);

    $best = app(OfferPerformanceReport::class)->summary(offerFilters($this->owner))[2];

    expect($best->value)->toBeNull()
        ->and($best->hint)->toBe('No vouchers used yet');
});
