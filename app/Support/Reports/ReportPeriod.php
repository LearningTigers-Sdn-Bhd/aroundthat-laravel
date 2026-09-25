<?php

namespace App\Support\Reports;

use App\Enums\ReportGrouping;
use App\Enums\ReportPeriodPreset;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use LogicException;

/**
 * The whole days a report covers, counted in the business's timezone. Both days are included.
 */
final readonly class ReportPeriod
{
    /** The longest custom range, so one report never reads more than a year of rows. */
    public const int MAX_DAYS = 366;

    private function __construct(
        public ReportPeriodPreset $preset,
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    /**
     * A ready-made range that ends today, or covers last month, in the timezone.
     */
    public static function preset(ReportPeriodPreset $preset, string $timezone): self
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $lastMonth = $today->subMonthNoOverflow();

        [$from, $to] = match ($preset) {
            ReportPeriodPreset::Today => [$today, $today],
            ReportPeriodPreset::Last7Days => [$today->subDays(6), $today],
            ReportPeriodPreset::Last30Days => [$today->subDays(29), $today],
            ReportPeriodPreset::ThisMonth => [$today->startOfMonth(), $today],
            ReportPeriodPreset::LastMonth => [$lastMonth->startOfMonth(), $lastMonth->endOfMonth()->startOfDay()],
            ReportPeriodPreset::Custom => throw new LogicException('A custom period needs its dates. Use custom().'),
        };

        return new self($preset, $from, $to);
    }

    /**
     * The days from and to, as `Y-m-d`. The range must not end after today or run past MAX_DAYS.
     *
     * @throws InvalidArgumentException with a message the member can read
     */
    public static function custom(string $from, string $to, string $timezone): self
    {
        $start = self::day($from, $timezone);
        $end = self::day($to, $timezone);

        if ($end->lt($start)) {
            throw new InvalidArgumentException(__('The end date must be on or after the start date.'));
        }

        if ($end->gt(CarbonImmutable::now($timezone)->startOfDay())) {
            throw new InvalidArgumentException(__('The end date cannot be in the future.'));
        }

        if ((int) round($start->diffInDays($end)) + 1 > self::MAX_DAYS) {
            throw new InvalidArgumentException(__('Choose :days days or fewer.', ['days' => self::MAX_DAYS]));
        }

        return new self(ReportPeriodPreset::Custom, $start, $end);
    }

    /**
     * A real calendar day as `Y-m-d`. Carbon would roll 30 February over into March, so the date must read back
     * the same.
     */
    private static function day(string $date, string $timezone): CarbonImmutable
    {
        try {
            $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        } catch (InvalidArgumentException) {
            $day = null;
        }

        if (! $day instanceof CarbonImmutable || $day->toDateString() !== $date) {
            throw new InvalidArgumentException(__('Enter both dates as a day, month and year.'));
        }

        return $day;
    }

    public function timezone(): string
    {
        return $this->from->timezoneName;
    }

    /**
     * The first moment of the period, in UTC.
     */
    public function startsAt(): CarbonImmutable
    {
        return $this->from->utc();
    }

    /**
     * The first moment after the period, in UTC. Compare with `<`.
     */
    public function endsAt(): CarbonImmutable
    {
        return $this->to->addDay()->utc();
    }

    /**
     * The range in words, such as "25 Sep 2026", "1–30 Sep 2026" or "28 Aug – 3 Sep 2026".
     */
    public function label(): string
    {
        if ($this->from->isSameDay($this->to)) {
            return $this->from->format('j M Y');
        }

        if ($this->from->isSameMonth($this->to)) {
            return $this->from->format('j').'–'.$this->to->format('j M Y');
        }

        if ($this->from->isSameYear($this->to)) {
            return $this->from->format('j M').' – '.$this->to->format('j M Y');
        }

        return $this->from->format('j M Y').' – '.$this->to->format('j M Y');
    }

    /**
     * Select `bucket`: the first day, as `Y-m-d`, of the day, week or month a timestamp column falls in, in the
     * business's timezone. Weeks start on Monday. The query is grouped and ordered by it.
     *
     * @param  literal-string  $column
     */
    public function selectBucket(Builder $query, string $column, ReportGrouping $grouping): Builder
    {
        $unit = match ($grouping) {
            ReportGrouping::Day => 'day',
            ReportGrouping::Week => 'week',
            ReportGrouping::Month => 'month',
            default => throw new LogicException("{$grouping->value} is not a time grouping."),
        };

        return $query
            ->selectRaw("to_char(date_trunc('{$unit}', {$column} at time zone ?), 'YYYY-MM-DD') as bucket", [$this->timezone()])
            ->groupBy('bucket')
            ->orderBy('bucket');
    }

    /**
     * The first day of every day, week or month the period touches, in order, so empty ones still get a row.
     *
     * @return list<CarbonImmutable>
     */
    public function buckets(ReportGrouping $grouping): array
    {
        $current = match ($grouping) {
            ReportGrouping::Day => $this->from,
            ReportGrouping::Week => $this->from->startOfWeek(CarbonImmutable::MONDAY),
            ReportGrouping::Month => $this->from->startOfMonth(),
            default => throw new LogicException("{$grouping->value} is not a time grouping."),
        };

        $buckets = [];

        while ($current->lte($this->to)) {
            $buckets[] = $current;
            $current = match ($grouping) {
                ReportGrouping::Day => $current->addDay(),
                ReportGrouping::Week => $current->addWeek(),
                default => $current->addMonthNoOverflow(),
            };
        }

        return $buckets;
    }
}
