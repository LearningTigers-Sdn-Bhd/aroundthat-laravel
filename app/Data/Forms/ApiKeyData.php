<?php

namespace App\Data\Forms;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A new API key: a name that says where it is used, and an optional Malaysian date it stops working.
 */
#[MapName(SnakeCaseMapper::class)]
class ApiKeyData extends Data
{
    public function __construct(
        #[Max(80)]
        public string $name,
        public ?string $expiresOn = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'expires_on' => ['nullable', 'date_format:Y-m-d', 'after:today'],
        ];
    }

    public function expiresAt(): ?Carbon
    {
        return $this->expiresOn === null
            ? null
            : Carbon::createFromFormat('Y-m-d', $this->expiresOn, IntegrationData::TIMEZONE)->startOfDay()->utc();
    }
}
