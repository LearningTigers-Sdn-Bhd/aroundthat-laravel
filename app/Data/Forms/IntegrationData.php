<?php

namespace App\Data\Forms;

use App\Enums\IntegrationCapability;
use App\Enums\IntegrationType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * An integration an admin adds or edits. Start and end are Malaysian dates: keys work from the start of `starts_on`
 * and stop at the start of `ends_on`. Both are stored in UTC, because dates are saved without their timezone.
 */
#[MapName(SnakeCaseMapper::class)]
class IntegrationData extends Data
{
    public const string TIMEZONE = 'Asia/Kuala_Lumpur';

    /**
     * @param  list<string>  $capabilities
     */
    public function __construct(
        #[Max(120)]
        public string $name,
        public IntegrationType $type,
        public array $capabilities = [],
        public ?string $startsOn = null,
        public ?string $endsOn = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'capabilities.*' => ['distinct', Rule::enum(IntegrationCapability::class)],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after:starts_on'],
        ];
    }

    /**
     * @return array{name: string, type: IntegrationType, capabilities: list<string>, starts_at: Carbon|null, expires_at: Carbon|null}
     */
    public function toModelAttributes(): array
    {
        return [
            'name' => trim($this->name),
            'type' => $this->type,
            'capabilities' => array_values(array_filter(
                array_map(fn (IntegrationCapability $capability): string => $capability->value, IntegrationCapability::cases()),
                fn (string $capability): bool => in_array($capability, $this->capabilities, true),
            )),
            'starts_at' => $this->startOfDay($this->startsOn),
            'expires_at' => $this->startOfDay($this->endsOn),
        ];
    }

    protected function startOfDay(?string $date): ?Carbon
    {
        return $date === null ? null : Carbon::createFromFormat('Y-m-d', $date, self::TIMEZONE)->startOfDay()->utc();
    }
}
