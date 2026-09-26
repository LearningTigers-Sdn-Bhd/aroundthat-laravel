<?php

namespace App\Support;

use App\Models\Outlet;
use App\Models\OutletDateException;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Opening periods in the outlet's local time, as HH:MM pairs.
 *
 * A closing time of 00:00 means the end of that day, so 22:00–00:00 is valid and 00:00–00:00 is open all day.
 * Overnight hours are two periods: 22:00–00:00 on one day and 00:00–02:00 on the next.
 */
class OpeningHours
{
    public const int MINUTES_PER_DAY = 1440;

    /**
     * ISO weekday numbers, Monday first. PHP stores the JSON keys "1" to "7" as these integers.
     *
     * @var list<int>
     */
    public const array DAYS = [1, 2, 3, 4, 5, 6, 7];

    /**
     * The week's periods, keyed by ISO weekday. Null when no day has a period, so "no hours set" has one form.
     *
     * @param  array<array-key, mixed>  $days
     * @return array<int, list<array{opens: string, closes: string}>>|null
     *
     * @throws ValidationException
     */
    public static function normalizeWeek(array $days): ?array
    {
        $week = [];

        foreach (self::DAYS as $day) {
            $periods = $days[$day] ?? [];
            $week[$day] = self::normalizePeriods(is_array($periods) ? $periods : [], "regular_hours.{$day}");
        }

        return array_merge(...array_values($week)) === [] ? null : $week;
    }

    /**
     * Check one day's periods and sort them by opening time.
     *
     * @param  array<array-key, mixed>  $periods
     * @return list<array{opens: string, closes: string}>
     *
     * @throws ValidationException
     */
    public static function normalizePeriods(array $periods, string $errorKey): array
    {
        $normalized = [];

        foreach ($periods as $period) {
            $opens = is_array($period) ? (string) ($period['opens'] ?? '') : '';
            $closes = is_array($period) ? (string) ($period['closes'] ?? '') : '';

            if (! self::isTime($opens) || ! self::isTime($closes)) {
                throw ValidationException::withMessages([$errorKey => __('Times must use HH:MM, such as 09:00.')]);
            }

            if (self::closingMinute($closes) <= self::minute($opens)) {
                throw ValidationException::withMessages([$errorKey => __('Closing time must be after opening time. Split overnight hours at midnight.')]);
            }

            $normalized[] = ['opens' => $opens, 'closes' => $closes];
        }

        usort($normalized, fn (array $a, array $b): int => self::minute($a['opens']) <=> self::minute($b['opens']));

        for ($index = 1; $index < count($normalized); $index++) {
            if (self::minute($normalized[$index]['opens']) < self::closingMinute($normalized[$index - 1]['closes'])) {
                throw ValidationException::withMessages([$errorKey => __('Opening periods on the same day must not overlap.')]);
            }
        }

        return $normalized;
    }

    /**
     * Whether the outlet is open at the given moment, and when that changes today. Reads the date's exception first.
     *
     * @return array{is_open: bool, closes_at: string|null, next_opens_at: string|null}
     */
    public static function openState(Outlet $outlet, CarbonInterface $now): array
    {
        $local = $now->copy()->setTimezone($outlet->timezone);
        $minute = self::minute($local->format('H:i'));

        /** @var OutletDateException|null $exception */
        $exception = $outlet->dateExceptions->first(fn (OutletDateException $exception): bool => $exception->date->isSameDay($local));

        $periods = $exception
            ? ($exception->is_closed ? [] : ($exception->periods ?? []))
            : ($outlet->regular_hours[$local->isoWeekday()] ?? []);

        foreach ($periods as $period) {
            if (self::minute($period['opens']) <= $minute && $minute < self::closingMinute($period['closes'])) {
                return ['is_open' => true, 'closes_at' => $period['closes'], 'next_opens_at' => null];
            }
        }

        $next = collect($periods)->first(fn (array $period): bool => self::minute($period['opens']) > $minute);

        return ['is_open' => false, 'closes_at' => null, 'next_opens_at' => $next['opens'] ?? null];
    }

    protected static function isTime(string $time): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    protected static function minute(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }

    protected static function closingMinute(string $time): int
    {
        return self::minute($time) === 0 ? self::MINUTES_PER_DAY : self::minute($time);
    }
}
