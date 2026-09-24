<?php

namespace App\Actions\Outlets;

use App\Data\Forms\OpeningHoursData;
use App\Models\Outlet;
use App\Models\OutletDateException;
use App\Support\ActivityLog\AuditTrail;
use App\Support\OpeningHours;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Save an outlet's weekly hours and its upcoming dates with different hours. Past dates are kept as they were.
 */
class UpdateOpeningHours
{
    public function __construct(protected AuditTrail $audit) {}

    /**
     * @throws ValidationException
     */
    public function handle(Outlet $outlet, OpeningHoursData $data): Outlet
    {
        return DB::transaction(function () use ($outlet, $data): Outlet {
            $outlet = $outlet->lockedForUpdate();

            if (! $outlet->isWritable()) {
                throw ValidationException::withMessages([
                    'outlet' => __('This outlet cannot be changed while it is archived, suspended or waiting for review.'),
                ]);
            }

            $regularHours = OpeningHours::normalizeWeek($data->regularHours ?? []);

            if ($regularHours === null && $outlet->is_listed) {
                throw ValidationException::withMessages([
                    'regular_hours' => __('A listed outlet needs opening hours. Unlist it on the Public page first.'),
                ]);
            }

            $today = Carbon::now($outlet->timezone)->toDateString();
            $exceptions = $this->exceptions($data->dateExceptions ?? [], $today);

            return $this->audit->as('hours_changed', null, function () use ($outlet, $regularHours, $exceptions, $today): Outlet {
                $outlet->update(['regular_hours' => $regularHours]);

                $previous = $this->describeUpcoming($outlet, $today);

                $outlet->dateExceptions()->where('date', '>=', $today)->delete();
                $outlet->dateExceptions()->createMany($exceptions);

                $current = $this->describeUpcoming($outlet, $today);

                if ($previous !== $current) {
                    $this->audit->record($outlet, 'date_exceptions_changed', null, [
                        'date_exceptions' => ['old' => $previous, 'new' => $current],
                    ]);
                }

                return $outlet;
            });
        });
    }

    /**
     * @param  list<array{date: string, is_closed: bool, periods?: list<array{opens: string, closes: string}>, note?: string|null}>  $exceptions
     * @return list<array{date: string, is_closed: bool, periods: list<array{opens: string, closes: string}>|null, note: string|null}>
     *
     * @throws ValidationException
     */
    protected function exceptions(array $exceptions, string $today): array
    {
        $normalized = [];

        foreach ($exceptions as $index => $exception) {
            if ($exception['date'] < $today) {
                throw ValidationException::withMessages(["date_exceptions.{$index}.date" => __('Choose today or a later date.')]);
            }

            $isClosed = (bool) $exception['is_closed'];
            $periods = $isClosed ? [] : OpeningHours::normalizePeriods($exception['periods'] ?? [], "date_exceptions.{$index}.periods");

            if (! $isClosed && $periods === []) {
                throw ValidationException::withMessages(["date_exceptions.{$index}.periods" => __('Add the hours for this date, or mark it closed.')]);
            }

            $normalized[] = [
                'date' => $exception['date'],
                'is_closed' => $isClosed,
                'periods' => $isClosed ? null : $periods,
                'note' => filled($exception['note'] ?? null) ? trim((string) $exception['note']) : null,
            ];
        }

        usort($normalized, fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return $normalized;
    }

    /**
     * @return list<string>
     */
    protected function describeUpcoming(Outlet $outlet, string $today): array
    {
        return array_values($outlet->dateExceptions()->where('date', '>=', $today)->get()
            ->map(fn (OutletDateException $exception): string => $exception->describe())
            ->all());
    }
}
