<?php

use App\Enums\EngagementEventType;
use App\Enums\ReportGrouping;
use App\Models\Business;
use App\Models\EngagementEvent;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Voucher;
use App\Support\Reports\ReportFilters;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportScope;
use App\Support\Reports\ReportTile;
use App\Support\Reports\Types\PlaceVisitsReport;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    $this->business = Business::factory()->approved()->create();
    $this->bangsar = Outlet::factory()->for($this->business)->approved()->create(['name' => 'Bangsar']);
    $this->klcc = Outlet::factory()->for($this->business)->approved()->create(['name' => 'KLCC']);
    $this->owner = Membership::factory()->owner()->for($this->business)->create();
});

function visited(Outlet $outlet, EngagementEventType $type, string $at, int $times = 1, bool $suspect = false): void
{
    EngagementEvent::factory()->count($times)->for($outlet)->create([
        'event_type' => $type,
        'occurred_at' => CarbonImmutable::parse($at, 'UTC'),
        'suspect' => $suspect,
    ]);
}

function claimedFrom(Outlet $outlet, string $at): Voucher
{
    return Voucher::factory()->claimedBy()->create(['outlet_id' => $outlet->id, 'created_at' => CarbonImmutable::parse($at, 'UTC')]);
}

function visitFilters(Membership $membership, ReportGrouping $grouping = ReportGrouping::Day): ReportFilters
{
    return new ReportFilters(
        scope: ReportScope::for($membership),
        period: ReportPeriod::custom('2026-09-01', '2026-09-02', 'Asia/Kuala_Lumpur'),
        grouping: $grouping,
    );
}

test('each local day counts every kind of visit and the vouchers claimed', function () {
    visited($this->bangsar, EngagementEventType::PlaceImpression, '2026-09-01 04:00', times: 5);
    visited($this->bangsar, EngagementEventType::PlaceView, '2026-09-01 04:00', times: 2);
    visited($this->bangsar, EngagementEventType::OutboundClick, '2026-09-01 04:00');
    claimedFrom($this->bangsar, '2026-09-01 05:00');
    visited($this->bangsar, EngagementEventType::PlaceView, '2026-09-01 16:30');

    $rows = app(PlaceVisitsReport::class)->rows(visitFilters($this->owner));

    expect($rows)->toBe([
        ['key' => '2026-09-01', 'label' => 'Tue, 1 Sep', 'shown' => 5, 'opened' => 2, 'clicked' => 1, 'claimed' => 1, 'claim_rate' => 50.0, 'hidden' => false],
        ['key' => '2026-09-02', 'label' => 'Wed, 2 Sep', 'shown' => 0, 'opened' => 1, 'clicked' => 0, 'claimed' => 0, 'claim_rate' => 0.0, 'hidden' => false],
    ]);
});

test('events a partner sent that look like abuse are left out', function () {
    visited($this->bangsar, EngagementEventType::PlaceView, '2026-09-01 04:00', times: 3, suspect: true);
    visited($this->bangsar, EngagementEventType::PlaceView, '2026-09-01 04:00');

    $tiles = app(PlaceVisitsReport::class)->summary(visitFilters($this->owner));

    expect($tiles[0]->value)->toBe(1);
});

test('the claim share is empty when no page was opened', function () {
    claimedFrom($this->bangsar, '2026-09-01 05:00');

    $rows = app(PlaceVisitsReport::class)->rows(visitFilters($this->owner));

    expect($rows[0]['claim_rate'])->toBeNull();
});

test('every outlet gets a row, most opened first, and archived ones only with visits', function () {
    visited($this->klcc, EngagementEventType::PlaceView, '2026-09-01 04:00', times: 2);
    Outlet::factory()->for($this->business)->archived()->create(['name' => 'Closed quiet']);
    $closedBusy = Outlet::factory()->for($this->business)->archived()->create(['name' => 'Closed busy']);
    visited($closedBusy, EngagementEventType::PlaceView, '2026-09-01 04:00');
    visited(Outlet::factory()->publiclyVisible()->create(), EngagementEventType::PlaceView, '2026-09-01 04:00', times: 9);

    $rows = app(PlaceVisitsReport::class)->rows(visitFilters($this->owner, ReportGrouping::Outlet));

    expect(array_column($rows, 'opened', 'label'))->toBe(['KLCC' => 2, 'Closed busy' => 1, 'Bangsar' => 0]);
});

test('a manager sees only the outlets they are assigned to', function () {
    $manager = Membership::factory()->manager()->for($this->business)->withOutlets($this->bangsar)->create();
    visited($this->bangsar, EngagementEventType::PlaceView, '2026-09-01 04:00');
    visited($this->klcc, EngagementEventType::PlaceView, '2026-09-01 04:00');
    claimedFrom($this->klcc, '2026-09-01 05:00');

    $tiles = app(PlaceVisitsReport::class)->summary(visitFilters($manager));

    expect(array_map(fn (ReportTile $tile): mixed => $tile->value, $tiles))->toBe([1, 0, 0]);
});

test('the page opened tile says how often the place was shown in lists', function () {
    visited($this->bangsar, EngagementEventType::PlaceImpression, '2026-09-01 04:00', times: 3);

    $tiles = app(PlaceVisitsReport::class)->summary(visitFilters($this->owner));

    expect($tiles[0]->hint)->toBe('Shown in lists 3 times');
});
