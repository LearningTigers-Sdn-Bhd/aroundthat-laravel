<?php

use App\Models\Outlet;
use App\Support\OpeningHours;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

test('periods are sorted and 00:00 closes at the end of the day', function () {
    expect(OpeningHours::normalizePeriods([
        ['opens' => '18:00', 'closes' => '00:00'],
        ['opens' => '08:00', 'closes' => '14:00'],
    ], 'day'))->toBe([
        ['opens' => '08:00', 'closes' => '14:00'],
        ['opens' => '18:00', 'closes' => '00:00'],
    ]);

    expect(OpeningHours::normalizePeriods([['opens' => '00:00', 'closes' => '00:00']], 'day'))->toHaveCount(1);
});

test('refuses periods that are malformed, backwards or overlapping', function (array $periods, string $message) {
    expect(fn () => OpeningHours::normalizePeriods($periods, 'day'))->toThrow(ValidationException::class, $message);
})->with([
    'malformed' => [[['opens' => '9am', 'closes' => '17:00']], 'Times must use HH:MM'],
    'backwards' => [[['opens' => '22:00', 'closes' => '02:00']], 'Split overnight hours at midnight'],
    'overlapping' => [[['opens' => '09:00', 'closes' => '14:00'], ['opens' => '13:00', 'closes' => '18:00']], 'must not overlap'],
]);

test('a week with no periods is stored as no hours', function () {
    expect(OpeningHours::normalizeWeek(['1' => [], '2' => []]))->toBeNull();
    expect(OpeningHours::normalizeWeek(['3' => [['opens' => '09:00', 'closes' => '17:00']]])['3'])->toHaveCount(1);
});

test('the open state uses the outlet time zone and its date exceptions', function () {
    $outlet = Outlet::factory()->create([
        'timezone' => 'Asia/Kuala_Lumpur',
        'regular_hours' => ['4' => [['opens' => '09:00', 'closes' => '17:00']], '5' => [['opens' => '09:00', 'closes' => '17:00']]],
    ]);

    // Thursday 2026-09-24 10:30 in Kuala Lumpur is 02:30 UTC.
    expect(OpeningHours::openState($outlet->load('dateExceptions'), Carbon::parse('2026-09-24 02:30', 'UTC')))
        ->toBe(['is_open' => true, 'closes_at' => '17:00', 'next_opens_at' => null]);

    expect(OpeningHours::openState($outlet, Carbon::parse('2026-09-24 00:30', 'UTC')))
        ->toBe(['is_open' => false, 'closes_at' => null, 'next_opens_at' => '09:00']);

    $outlet->dateExceptions()->create(['date' => '2026-09-25', 'is_closed' => true]);

    expect(OpeningHours::openState($outlet->load('dateExceptions'), Carbon::parse('2026-09-25 02:30', 'UTC'))['is_open'])->toBeFalse();
});
