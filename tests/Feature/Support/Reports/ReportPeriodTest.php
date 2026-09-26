<?php

use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use App\Support\Reports\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

const KL = 'Asia/Kuala_Lumpur';

test('this month starts at local midnight even while UTC is still on the day before', function () {
    $this->travelTo(CarbonImmutable::parse('2026-08-31 16:30:00', 'UTC'));

    $period = ReportPeriod::preset(ReportPeriodPreset::ThisMonth, KL);

    expect($period->label())->toBe('1 Sep 2026')
        ->and($period->startsAt()->toIso8601String())->toBe('2026-08-31T16:00:00+00:00')
        ->and($period->endsAt()->toIso8601String())->toBe('2026-09-01T16:00:00+00:00');
});

test('the presets cover whole local days up to today', function (ReportPeriodPreset $preset, string $label) {
    $this->travelTo(CarbonImmutable::parse('2026-03-15 04:00:00', 'UTC'));

    expect(ReportPeriod::preset($preset, KL)->label())->toBe($label);
})->with([
    'today' => [ReportPeriodPreset::Today, '15 Mar 2026'],
    'last 7 days' => [ReportPeriodPreset::Last7Days, '9–15 Mar 2026'],
    'last 30 days' => [ReportPeriodPreset::Last30Days, '14 Feb – 15 Mar 2026'],
    'this month' => [ReportPeriodPreset::ThisMonth, '1–15 Mar 2026'],
    'last month' => [ReportPeriodPreset::LastMonth, '1–28 Feb 2026'],
]);

test('last month ends at the local midnight after its last day', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-15 04:00:00', 'UTC'));

    expect(ReportPeriod::preset(ReportPeriodPreset::LastMonth, KL)->endsAt()->toIso8601String())->toBe('2026-02-28T16:00:00+00:00');
});

test('a custom range may end on the local today while UTC is a day behind', function () {
    $this->travelTo(CarbonImmutable::parse('2025-12-31 17:00:00', 'UTC'));

    expect(ReportPeriod::custom('2025-12-20', '2026-01-01', KL)->label())->toBe('20 Dec 2025 – 1 Jan 2026');
});

test('a custom range is refused when it cannot be read', function (string $from, string $to, string $message) {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    expect(fn () => ReportPeriod::custom($from, $to, KL))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'not a date' => ['2026-09-01', '25/09/2026', 'Enter both dates as a day, month and year.'],
    'ends before it starts' => ['2026-09-10', '2026-09-09', 'The end date must be on or after the start date.'],
    'ends in the future' => ['2026-09-01', '2026-09-26', 'The end date cannot be in the future.'],
    'not on the calendar' => ['2026-02-30', '2026-03-01', 'Enter both dates as a day, month and year.'],
    'longer than 366 days' => ['2025-09-23', '2026-09-24', 'Choose 366 days or fewer.'],
]);

test('a custom range of exactly 366 days is allowed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    expect(ReportPeriod::custom('2025-09-24', '2026-09-24', KL)->label())->toBe('24 Sep 2025 – 24 Sep 2026');
});

test('timestamps are grouped by the local day, week and month', function (ReportGrouping $grouping, array $expected) {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));
    $period = ReportPeriod::preset(ReportPeriodPreset::ThisMonth, KL);
    $query = DB::query()->fromRaw("(values ('2026-08-31 15:59:00+00'::timestamptz), ('2026-08-31 16:00:00+00'::timestamptz), ('2026-09-07 03:00:00+00'::timestamptz)) as moments(at)");

    $rows = $period->selectBucket($query, 'moments.at', $grouping)->selectRaw('count(*) as moments')->get();

    expect($rows->pluck('moments', 'bucket')->all())->toBe($expected);
})->with([
    'day' => [ReportGrouping::Day, ['2026-08-31' => 1, '2026-09-01' => 1, '2026-09-07' => 1]],
    'week' => [ReportGrouping::Week, ['2026-08-31' => 2, '2026-09-07' => 1]],
    'month' => [ReportGrouping::Month, ['2026-08-01' => 1, '2026-09-01' => 2]],
]);

test('every day, week and month the range touches gets a bucket', function (ReportGrouping $grouping, array $expected) {
    $this->travelTo(CarbonImmutable::parse('2026-09-25 04:00:00', 'UTC'));

    $buckets = ReportPeriod::custom('2026-08-30', '2026-09-02', KL)->buckets($grouping);

    expect(array_map(fn (CarbonImmutable $start): string => $start->toDateString(), $buckets))->toBe($expected);
})->with([
    'day' => [ReportGrouping::Day, ['2026-08-30', '2026-08-31', '2026-09-01', '2026-09-02']],
    'week' => [ReportGrouping::Week, ['2026-08-24', '2026-08-31']],
    'month' => [ReportGrouping::Month, ['2026-08-01', '2026-09-01']],
]);
