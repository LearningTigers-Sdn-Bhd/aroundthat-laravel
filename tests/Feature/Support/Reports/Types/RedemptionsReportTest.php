<?php

use App\Enums\ReportGrouping;
use App\Models\Business;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Redemption;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportScope;
use App\Support\Reports\ReportTile;
use App\Support\Reports\Types\RedemptionsReport;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    $this->business = Business::factory()->approved()->create();
    $this->bangsar = Outlet::factory()->for($this->business)->approved()->create(['name' => 'Bangsar']);
    $this->klcc = Outlet::factory()->for($this->business)->approved()->create(['name' => 'KLCC']);
    $this->offer = VoucherOffer::factory()->for($this->business)->create(['name' => 'Ten off']);
});

function redeemed(VoucherOffer $offer, Outlet $outlet, string $at, string $bill = '100.00', string $discount = '10.00', bool $cancelled = false): Redemption
{
    return Redemption::factory()
        ->when($cancelled, fn ($factory) => $factory->cancelled())
        ->for(Voucher::factory()->for($offer, 'offer'))
        ->for($outlet)
        ->create(['redeemed_at' => CarbonImmutable::parse($at, 'UTC'), 'bill_amount' => $bill, 'discount_amount' => $discount]);
}

function redemptionFilters(Membership $membership, ReportGrouping $grouping = ReportGrouping::Day, ?string $offerId = null, ?string $outletId = null): ReportFilters
{
    return new ReportFilters(
        scope: ReportScope::for($membership),
        period: ReportPeriod::custom('2026-09-01', '2026-09-03', 'Asia/Kuala_Lumpur'),
        grouping: $grouping,
        offerId: $offerId,
        outletId: $outletId,
    );
}

function tileValues(array $tiles): array
{
    return array_map(fn (ReportTile $tile): mixed => $tile->value, $tiles);
}

test('cancelled redemptions are left out of the totals and only counted', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    redeemed($this->offer, $this->bangsar, '2026-09-01 04:00', '80.00', '8.00');
    redeemed($this->offer, $this->bangsar, '2026-09-01 05:00', '50.00', '5.00', cancelled: true);

    $tiles = app(RedemptionsReport::class)->summary(redemptionFilters($owner));

    expect(tileValues($tiles))->toBe([1, '80.00', '8.00', '72.00'])
        ->and($tiles[0]->hint)->toBe('1 cancelled, not counted');
});

test('each local day of the period gets a row, empty days included', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    redeemed($this->offer, $this->bangsar, '2026-09-01 16:30');

    $rows = app(RedemptionsReport::class)->rows(redemptionFilters($owner));

    expect(array_column($rows, 'used', 'key'))->toBe(['2026-09-01' => 0, '2026-09-02' => 1, '2026-09-03' => 0])
        ->and($rows[1]['label'])->toBe('Wed, 2 Sep');
});

test('owners see their offers used at a sponsored outlet of another business', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    $partnerOutlet = Outlet::factory()->approved()->create(['name' => 'Partner Cafe']);
    redeemed($this->offer, $partnerOutlet, '2026-09-02 04:00');

    $rows = app(RedemptionsReport::class)->rows(redemptionFilters($owner, ReportGrouping::Outlet));

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['label'])->toBe("Partner Cafe ({$partnerOutlet->business->name})");
});

test('managers see only the outlets they are assigned to', function () {
    $manager = Membership::factory()->manager()->for($this->business)->withOutlets($this->bangsar)->create();
    redeemed($this->offer, $this->bangsar, '2026-09-02 04:00');
    redeemed($this->offer, $this->klcc, '2026-09-02 04:00');
    redeemed($this->offer, Outlet::factory()->approved()->create(), '2026-09-02 04:00');

    $rows = app(RedemptionsReport::class)->rows(redemptionFilters($manager, ReportGrouping::Outlet));

    expect(array_column($rows, 'label'))->toBe(['Bangsar']);
});

test('another business using its own offers at its own outlets never appears', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    $otherOutlet = Outlet::factory()->approved()->create();
    redeemed(VoucherOffer::factory()->for($otherOutlet->business)->create(), $otherOutlet, '2026-09-02 04:00');

    $tiles = app(RedemptionsReport::class)->summary(redemptionFilters($owner));

    expect($tiles[0]->value)->toBe(0);
});

test('another business offer used at your outlet is named with that business', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    $sponsor = Business::factory()->approved()->create(['name' => 'Grand Hotel']);
    redeemed(VoucherOffer::factory()->for($sponsor)->create(['name' => 'Free coffee']), $this->bangsar, '2026-09-02 04:00');
    redeemed($this->offer, $this->bangsar, '2026-09-02 05:00');
    redeemed($this->offer, $this->bangsar, '2026-09-02 06:00');

    $rows = app(RedemptionsReport::class)->rows(redemptionFilters($owner, ReportGrouping::Offer));

    expect(array_column($rows, 'used', 'label'))->toBe(['Ten off' => 2, 'Free coffee (Grand Hotel)' => 1]);
});

test('a lone small sponsored outlet is hidden along with the totals', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    redeemed($this->offer, $this->bangsar, '2026-09-02 04:00');
    redeemed($this->offer, Outlet::factory()->approved()->create(), '2026-09-02 04:00');
    $filters = redemptionFilters($owner, ReportGrouping::Outlet);

    $rows = app(RedemptionsReport::class)->rows($filters);
    $tiles = app(RedemptionsReport::class)->summary($filters);

    expect(array_column($rows, 'hidden'))->toBe([false, true])
        ->and($rows[1]['used'])->toBeNull()
        ->and(tileValues($tiles))->toBe([null, null, null, null]);
});

test('the offer and outlet choices narrow the redemptions', function () {
    $owner = Membership::factory()->owner()->for($this->business)->create();
    $otherOffer = VoucherOffer::factory()->for($this->business)->create();
    redeemed($this->offer, $this->bangsar, '2026-09-02 04:00');
    redeemed($this->offer, $this->klcc, '2026-09-02 04:00');
    redeemed($otherOffer, $this->bangsar, '2026-09-02 04:00');
    redeemed($this->offer, Outlet::factory()->approved()->create(), '2026-09-02 04:00');

    $tiles = app(RedemptionsReport::class)->summary(redemptionFilters($owner, offerId: $this->offer->id, outletId: $this->bangsar->id));

    expect($tiles[0]->value)->toBe(1);
});
