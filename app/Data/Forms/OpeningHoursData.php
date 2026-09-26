<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An outlet's weekly hours and its upcoming dates with different hours. App\Support\OpeningHours checks the periods.
 */
#[MapName(SnakeCaseMapper::class), MergeValidationRules]
class OpeningHoursData extends Data
{
    /**
     * @param  array<int, list<array{opens: string, closes: string}>>|null  $regularHours  Keyed by ISO weekday.
     * @param  list<array{date: string, is_closed: bool, periods?: list<array{opens: string, closes: string}>, note?: string|null}>|null  $dateExceptions
     */
    public function __construct(
        public ?array $regularHours = null,
        public ?array $dateExceptions = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'regular_hours.*' => ['array'],
            'regular_hours.*.*.opens' => ['required', 'string'],
            'regular_hours.*.*.closes' => ['required', 'string'],
            'date_exceptions' => ['max:60'],
            'date_exceptions.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'date_exceptions.*.is_closed' => ['required', 'boolean'],
            'date_exceptions.*.periods' => ['array'],
            'date_exceptions.*.periods.*.opens' => ['required', 'string'],
            'date_exceptions.*.periods.*.closes' => ['required', 'string'],
            'date_exceptions.*.note' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'date_exceptions.*.date.distinct' => __('Each date can be listed once.'),
        ];
    }
}
