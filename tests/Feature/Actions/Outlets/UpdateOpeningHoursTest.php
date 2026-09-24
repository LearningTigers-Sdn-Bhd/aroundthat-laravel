<?php

use App\Actions\Outlets\UpdateOpeningHours;
use App\Data\Forms\OpeningHoursData;
use App\Models\Activity;
use App\Models\Business;
use App\Models\Outlet;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-24 02:00', 'UTC'));
    $this->outlet = Outlet::factory()->for(Business::factory()->approved())->approved()->create(['timezone' => 'Asia/Kuala_Lumpur']);
});

function hours(array $overrides = []): OpeningHoursData
{
    return OpeningHoursData::from([
        'regular_hours' => ['1' => [['opens' => '09:00', 'closes' => '17:00']]],
        'date_exceptions' => [],
        ...$overrides,
    ]);
}

test('saves the week and upcoming dates and keeps past dates', function () {
    $this->outlet->dateExceptions()->create(['date' => '2026-01-01', 'is_closed' => true]);

    app(UpdateOpeningHours::class)->handle($this->outlet, hours([
        'date_exceptions' => [
            ['date' => '2026-12-25', 'is_closed' => true, 'note' => 'Christmas'],
            ['date' => '2026-10-20', 'is_closed' => false, 'periods' => [['opens' => '10:00', 'closes' => '14:00']]],
        ],
    ]));

    $this->outlet->refresh();
    expect($this->outlet->regular_hours['1'])->toBe([['opens' => '09:00', 'closes' => '17:00']]);
    expect($this->outlet->regular_hours['2'])->toBe([]);
    expect($this->outlet->dateExceptions->map->describe()->all())->toBe([
        '2026-01-01: closed',
        '2026-10-20: 10:00–14:00',
        '2026-12-25: closed (Christmas)',
    ]);

    $logged = Activity::forSubject($this->outlet)->where('event', 'date_exceptions_changed')->sole();
    expect($logged->properties->get('date_exceptions')['new'])->toBe(['2026-10-20: 10:00–14:00', '2026-12-25: closed (Christmas)']);
});

test('refuses a date in the past and an open date without hours', function (array $exception, string $message) {
    expect(fn () => app(UpdateOpeningHours::class)->handle($this->outlet, hours(['date_exceptions' => [$exception]])))
        ->toThrow(ValidationException::class, $message);
})->with([
    'past' => [['date' => '2026-09-23', 'is_closed' => true], 'Choose today or a later date.'],
    'open without hours' => [['date' => '2026-10-01', 'is_closed' => false, 'periods' => []], 'Add the hours for this date'],
]);

test('a listed outlet cannot lose all its hours', function () {
    $this->outlet->forceFill(['is_listed' => true])->save();

    app(UpdateOpeningHours::class)->handle($this->outlet, hours(['regular_hours' => ['1' => []]]));
})->throws(ValidationException::class, 'A listed outlet needs opening hours.');
