<?php

namespace App\Data;

use App\Support\Locations;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The choices for the country, state and timezone fields.
 */
#[MapName(SnakeCaseMapper::class)]
class LocationOptionsData extends Data
{
    /**
     * @param  list<string>  $timezones
     * @param  list<string>  $countryCodes
     * @param  list<string>  $malaysianStates
     */
    public function __construct(
        public array $timezones,
        public array $countryCodes,
        public array $malaysianStates,
    ) {}

    public static function current(): self
    {
        return new self(
            timezones: Locations::timezones(),
            countryCodes: Locations::COUNTRY_CODES,
            malaysianStates: Locations::MALAYSIAN_STATES,
        );
    }
}
